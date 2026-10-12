#!/usr/bin/env bash
# Port-forwards a local port to the eXist-db ECS task through the bastion
# host, using an SSM Session Manager port-forwarding session - no SSH keys,
# no inbound ports on the bastion. Once it's up, http://localhost:8086/exist/
# reaches eXist-db's own port 8086 in the target environment.
#
# Steps:
#   1. Finds the ECS cluster and the eXist-db service (any service whose name
#      contains "existdb") and looks up a running task's private IP.
#   2. Finds the running bastion instance (Name tag containing "bastion").
#   3. Opens `aws ssm start-session` with the
#      AWS-StartPortForwardingSessionToRemoteHost document from the bastion
#      to <task IP>:8086, bound to localhost:<local port>.
# Whenever a step has more than one candidate, a lettered picker is shown
# (same as ecs-exec.sh).
#
# Requires:
#   - AWS CLI v2
#   - Session Manager plugin on PATH:
#     https://docs.aws.amazon.com/systems-manager/latest/userguide/session-manager-working-with-install-plugin.html
#   - IAM permissions: ecs:ListClusters, ecs:ListServices, ecs:ListTasks,
#     ecs:DescribeTasks, ec2:DescribeInstances, ssm:StartSession
#   - The bastion's security group allowed to reach the eXist-db service on
#     its port (already the case in this project's security groups)
#
# Written against bash 3.2 (macOS's stock /bin/bash), no jq - same
# conventions (and the same reasons for no `set -u`) as ecs-exec.sh.
set -eo pipefail

PROFILE=""
REGION=""
LOCAL_PORT="8086"
REMOTE_PORT="8086"

usage() {
  cat <<'USAGE'
Usage: existdb-tunnel.sh [-p|--profile PROFILE] [-r|--region REGION] [-l|--local-port PORT]

Opens an SSM port-forwarding session through the bastion host so that
localhost:<local port> reaches the eXist-db ECS task's port 8086.

  -p, --profile      AWS CLI profile to use (optional, falls back to the
                      default credential chain / AWS_PROFILE env var)
  -r, --region       AWS region to use (optional, falls back to the
                      profile's configured region)
  -l, --local-port   Local port to listen on (default: 8086)
  -h, --help         Show this help
USAGE
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    -p|--profile) PROFILE="$2"; shift 2 ;;
    -r|--region) REGION="$2"; shift 2 ;;
    -l|--local-port) LOCAL_PORT="$2"; shift 2 ;;
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
  echo "session-manager-plugin not found on PATH - required for 'aws ssm start-session'." >&2
  echo "See: https://docs.aws.amazon.com/systems-manager/latest/userguide/session-manager-working-with-install-plugin.html" >&2
  exit 1
}

# Same helper as ecs-exec.sh: runs an `--output text` list query, fails
# loudly on aws errors, splits the tab/newline-separated result into the
# array named by $1.
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
    [[ -n "$__line" && "$__line" != "None" ]] && eval "$__varname+=(\"\$__line\")"
  done <<< "$(printf '%s' "$__output" | tr '\t' '\n')"
}

# Same picker as ecs-exec.sh. Prints the chosen item on stdout, everything
# else on stderr.
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

echo "Looking up eXist-db services in ${CLUSTER_NAME}..." >&2
list_into SERVICE_ARNS ecs list-services --cluster "$CLUSTER_ARN" \
  --query "serviceArns[?contains(@, 'existdb')]" --output text
SERVICE_ARN=$(select_one "eXist-db service" "${SERVICE_ARNS[@]}")
SERVICE_NAME="${SERVICE_ARN##*/}"

echo "Looking up running tasks for ${SERVICE_NAME}..." >&2
list_into TASK_ARNS ecs list-tasks --cluster "$CLUSTER_ARN" --service-name "$SERVICE_NAME" \
  --desired-status RUNNING --query 'taskArns[]' --output text
TASK_ARN=$(select_one "task" "${TASK_ARNS[@]}")
TASK_ID="${TASK_ARN##*/}"

list_into TASK_IPS ecs describe-tasks --cluster "$CLUSTER_ARN" --tasks "$TASK_ARN" \
  --query 'tasks[0].containers[0].networkInterfaces[0].privateIpv4Address' --output text
if [[ ${#TASK_IPS[@]} -eq 0 ]]; then
  echo "Task ${TASK_ID} has no private IP yet (still provisioning?) - try again shortly." >&2
  exit 1
fi
TASK_IP="${TASK_IPS[0]}"
echo "eXist-db task ${TASK_ID} is at ${TASK_IP}" >&2

echo "Looking up running bastion hosts..." >&2
list_into BASTIONS ec2 describe-instances \
  --filters "Name=tag:Name,Values=*bastion*" "Name=instance-state-name,Values=running" \
  --query 'Reservations[].Instances[].[InstanceId]' --output text
BASTION_ID=$(select_one "bastion instance" "${BASTIONS[@]}")

echo "Forwarding localhost:${LOCAL_PORT} -> ${BASTION_ID} -> ${TASK_IP}:${REMOTE_PORT} (Ctrl+C to stop)" >&2
echo "eXist-db will be at http://localhost:${LOCAL_PORT}/exist/" >&2
aws_cli ssm start-session \
  --target "$BASTION_ID" \
  --document-name AWS-StartPortForwardingSessionToRemoteHost \
  --parameters "host=${TASK_IP},portNumber=${REMOTE_PORT},localPortNumber=${LOCAL_PORT}"
