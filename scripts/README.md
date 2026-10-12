# ECS Exec scripts

General-purpose CLI wizard for opening an interactive shell inside a running
ECS Fargate task's container via ECS Exec (`aws ecs execute-command`). Not
tied to any specific project's cluster/service naming - point it at an AWS
CLI profile/region and it works against whatever ECS clusters exist there,
so it's reusable across projects, not just this one.

It walks through four steps - clusters, services, tasks, containers -
auto-picking whenever there's only one option, and otherwise showing a
lettered list (`a`, `b`, `c`, ...) to choose from (a plain `read`-based
menu, since neither bash nor PowerShell can portably do an arrow-key picker
without extra dependencies).

## Prerequisites

- A valid, logged-in AWS session for the target account *before* running
  either script - e.g. `aws sso login --profile <profile>` (or
  `aws-vault exec <profile> -- ...`/an active SSO session), whatever your
  setup normally uses. Neither script logs you in; if your credentials
  have expired, the AWS CLI will fail with its own error (e.g. "Token has
  expired and refresh failed") and the script exits - log in again and
  re-run it.
- AWS CLI v2 (`aws ecs execute-command` support)
- Session Manager plugin for the AWS CLI, on `PATH`:
  https://docs.aws.amazon.com/systems-manager/latest/userguide/session-manager-working-with-install-plugin.html
- IAM permissions on the target account/role: `ecs:ListClusters`,
  `ecs:ListServices`, `ecs:ListTasks`, `ecs:DescribeTasks`,
  `ecs:ExecuteCommand`, `ssm:StartSession`
- The target ECS service must have ECS Exec enabled
  (`enableExecuteCommand: true` at the service level)

Neither script needs anything beyond that (no `jq`, no AWS SDK/PowerShell
modules) - both just shell out to `aws` and parse its output.

## Usage

Bash (macOS/Linux, works with the stock bash 3.2 that ships on macOS):

```
./ecs-exec.sh [-p|--profile PROFILE] [-r|--region REGION] [-c|--command COMMAND]
```

PowerShell (Windows PowerShell 5.1+ or pwsh on any OS):

```
./ecs-exec.ps1 [-ProfileName PROFILE] [-Region REGION] [-Command COMMAND]
```

Both default the in-container command to `/bin/sh` and the AWS profile/
region to whatever the AWS CLI would otherwise use by default (env vars,
default profile, etc.) if not given explicitly.

## How it works

1. Lists ECS clusters in the account/region.
2. Lists services in the chosen cluster.
3. Lists running tasks for the chosen service.
4. Lists containers in the chosen task.
5. Opens `aws ecs execute-command --interactive` into the chosen container.

At each of steps 1-4: zero matches is an error, exactly one is auto-selected
with a note printed, and more than one shows the lettered picker. Each step
supports up to 26 choices (one per letter) - past that it errors out rather
than silently truncating.

# eXist-db tunnel scripts

`existdb-tunnel.sh` / `existdb-tunnel.ps1` open an SSM Session Manager
port-forwarding session through the bastion host so that
`http://localhost:8086/exist/` reaches the eXist-db ECS task's port 8086 in
whichever environment the given profile/region points at. No SSH keys and no
inbound ports on the bastion are needed.

## Prerequisites

Same AWS CLI v2 / Session Manager plugin / logged-in session requirements as
above, plus IAM permissions: `ecs:ListClusters`, `ecs:ListServices`,
`ecs:ListTasks`, `ecs:DescribeTasks`, `ec2:DescribeInstances`,
`ssm:StartSession`.

## Usage

```
./existdb-tunnel.sh [-p|--profile PROFILE] [-r|--region REGION] [-l|--local-port PORT]
./existdb-tunnel.ps1 [-ProfileName PROFILE] [-Region REGION] [-LocalPort PORT]
```

The local port defaults to 8086. Leave the script running while you use the
tunnel and press Ctrl+C to close it.

## How it works

1. Lists ECS clusters, then the services in the chosen cluster whose name
   contains `existdb`, then that service's running tasks.
2. Reads the chosen task's private IP.
3. Finds running EC2 instances whose `Name` tag contains `bastion`.
4. Runs `aws ssm start-session` with the
   `AWS-StartPortForwardingSessionToRemoteHost` document, forwarding
   `localhost:<local port>` to `<task IP>:8086` via the bastion.

Each step uses the same auto-pick / lettered-picker behaviour as the ECS Exec
scripts. The task IP changes whenever the task is replaced, for example on a
deploy. If that happens, re-run the script.
