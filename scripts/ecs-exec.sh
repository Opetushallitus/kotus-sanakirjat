#!/usr/bin/env bash
# ECS Exec wizard: point it at an AWS CLI profile/region and it walks you
# through picking a cluster -> service -> task -> container, then opens an
# ECS Exec shell into it. General-purpose - not tied to any specific
# project's cluster/service naming, so it works as-is against any account.
#
# No dependencies beyond the AWS CLI and the Session Manager plugin - every
# AWS CLI call below is parsed with `--output text`, no jq or anything else.
#
# Requires:
#   - AWS CLI v2 (for `aws ecs execute-command`)
#   - Session Manager plugin on PATH:
#     https://docs.aws.amazon.com/systems-manager/latest/userguide/session-manager-working-with-install-plugin.html
#   - IAM permissions: ecs:ListClusters, ecs:ListServices, ecs:ListTasks,
#     ecs:DescribeTasks, ecs:ExecuteCommand, ssm:StartSession
#   - ECS Exec enabled on the target service (`enableExecuteCommand: true`)
#
# Written against bash 3.2 (macOS's stock /bin/bash) deliberately - no
# mapfile/readarray, no ${var,,} case conversion, nothing bash-4-only.
#
# Deliberately no `set -u`: bash versions before 4.4 raise a spurious
# "unbound variable" error when expanding "${arr[@]}" on a zero-element
# array even though the array itself is perfectly well-defined - and "zero
# items found" (e.g. no ECS clusters in this account/region) is a real,
# expected case here, not a bug to guard against.
set -eo pipefail

PROFILE=""
REGION=""
COMMAND="/bin/sh"

usage() {
  cat <<'USAGE'
Usage: ecs-exec.sh [-p|--profile PROFILE] [-r|--region REGION] [-c|--command COMMAND]

Interactively pick an ECS cluster, service, task, and container, then open
an ECS Exec shell into it (aws ecs execute-command --interactive).

  -p, --profile   AWS CLI profile to use (optional, falls back to the
                   default credential chain / AWS_PROFILE env var)
  -r, --region    AWS region to use (optional, falls back to the profile's
                   configured region)
  -c, --command   Command to run in the container (default: /bin/sh)
  -h, --help      Show this help
USAGE
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    -p|--profile) PROFILE="$2"; shift 2 ;;
    -r|--region) REGION="$2"; shift 2 ;;
    -c|--command) COMMAND="$2"; shift 2 ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown argument: $1" >&2; usage; exit 1 ;;
  esac
done

AWS_ARGS=()
[[ -n "$PROFILE" ]] && AWS_ARGS+=(--profile "$PROFILE")
[[ -n "$REGION" ]] && AWS_ARGS+=(--region "$REGION")

aws_cli() {
  aws "${AWS_ARGS[@]}" "$@"
}

command -v aws >/dev/null 2>&1 || { echo "aws CLI not found on PATH." >&2; exit 1; }
command -v session-manager-plugin >/dev/null 2>&1 || {
  echo "session-manager-plugin not found on PATH - required for 'aws ecs execute-command --interactive'." >&2
  echo "See: https://docs.aws.amazon.com/systems-manager/latest/userguide/session-manager-working-with-install-plugin.html" >&2
  exit 1
}

# Runs `aws_cli "$@"` (expected to be an `--output text` list query), fails
# loudly if the aws call itself errors (e.g. expired SSO token - rather
# than silently treating that the same as "zero items found"), and splits
# the tab-separated result into the array named by $1. Bash-3.2-safe
# replacement for mapfile/readarray.
list_into() {
  local __varname="$1"; shift
  local __output
  if ! __output=$(aws_cli "$@"); then
    echo "aws ${*} failed - check your credentials/profile/SSO login (see the error above)." >&2
    exit 1
  fi
  eval "$__varname=()"
  local __line
  while IFS= read -r __line; do
    [[ -n "$__line" ]] && eval "$__varname+=(\"\$__line\")"
  done <<< "$(printf '%s' "$__output" | tr '\t' '\n')"
}

# Prints the chosen item on stdout (everything else on stderr so this is
# safe to call as `x=$(select_one ...)`). $1: human label for prompts/
# errors, the rest: candidate items.
select_one() {
  local label="$1"; shift
  local items=("$@")

  if [[ ${#items[@]} -eq 0 ]]; then
    echo "No ${label} found." >&2
    exit 1
  fi

  if [[ ${#items[@]} -eq 1 ]]; then
    echo "Only one ${label} found, using it: ${items[0]}" >&2
    printf '%s\n' "${items[0]}"
    return
  fi

  local letters=(a b c d e f g h i j k l m n o p q r s t u v w x y z)
  if [[ ${#items[@]} -gt ${#letters[@]} ]]; then
    echo "More than ${#letters[@]} ${label}s found - this tool only supports up to ${#letters[@]} choices at once." >&2
    exit 1
  fi

  echo "Select a ${label}:" >&2
  local i
  for i in "${!items[@]}"; do
    echo "  ${letters[$i]}) ${items[$i]}" >&2
  done

  local choice index
  while true; do
    printf '> ' >&2
    read -r choice < /dev/tty
    choice=$(printf '%s' "$choice" | tr '[:upper:]' '[:lower:]')
    index=-1
    for i in "${!items[@]}"; do
      [[ "${letters[$i]}" == "$choice" ]] && index=$i && break
    done
    if [[ $index -ge 0 ]]; then
      printf '%s\n' "${items[$index]}"
      return
    fi
    echo "Invalid choice, try again." >&2
  done
}

echo "Looking up ECS clusters..." >&2
list_into CLUSTER_ARNS ecs list-clusters --query 'clusterArns[]' --output text
CLUSTER_ARN=$(select_one "cluster" "${CLUSTER_ARNS[@]}")
CLUSTER_NAME="${CLUSTER_ARN##*/}"

echo "Looking up services in ${CLUSTER_NAME}..." >&2
list_into SERVICE_ARNS ecs list-services --cluster "$CLUSTER_ARN" --query 'serviceArns[]' --output text
SERVICE_ARN=$(select_one "service" "${SERVICE_ARNS[@]}")
SERVICE_NAME="${SERVICE_ARN##*/}"

echo "Looking up running tasks for ${SERVICE_NAME}..." >&2
list_into TASK_ARNS ecs list-tasks --cluster "$CLUSTER_ARN" --service-name "$SERVICE_NAME" --desired-status RUNNING --query 'taskArns[]' --output text
TASK_ARN=$(select_one "task" "${TASK_ARNS[@]}")
TASK_ID="${TASK_ARN##*/}"

echo "Looking up containers in task ${TASK_ID}..." >&2
list_into CONTAINERS ecs describe-tasks --cluster "$CLUSTER_ARN" --tasks "$TASK_ARN" --query 'tasks[0].containers[].name' --output text
CONTAINER=$(select_one "container" "${CONTAINERS[@]}")

echo "Opening ECS Exec shell: cluster=${CLUSTER_NAME} task=${TASK_ID} container=${CONTAINER} command=${COMMAND}" >&2
aws_cli ecs execute-command \
  --cluster "$CLUSTER_ARN" \
  --task "$TASK_ARN" \
  --container "$CONTAINER" \
  --interactive \
  --command "$COMMAND"
