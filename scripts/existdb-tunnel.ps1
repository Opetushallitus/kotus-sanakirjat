<#
.SYNOPSIS
  Port-forwards a local port to the eXist-db ECS task through the bastion
  host, using an SSM Session Manager port-forwarding session.

.DESCRIPTION
  Once it's up, http://localhost:8086/exist/ reaches eXist-db's own port
  8086 in the target environment - no SSH keys, no inbound ports on the
  bastion.

  Steps:
    1. Finds the ECS cluster and the eXist-db service (any service whose
       name contains "existdb") and looks up a running task's private IP.
    2. Finds the running bastion instance (Name tag containing "bastion").
    3. Opens `aws ssm start-session` with the
       AWS-StartPortForwardingSessionToRemoteHost document from the bastion
       to <task IP>:8086, bound to localhost:<LocalPort>.
  Whenever a step has more than one candidate, a lettered picker is shown
  (same as ecs-exec.ps1).

  Requires:
    - AWS CLI v2
    - Session Manager plugin on PATH:
      https://docs.aws.amazon.com/systems-manager/latest/userguide/session-manager-working-with-install-plugin.html
    - IAM permissions: ecs:ListClusters, ecs:ListServices, ecs:ListTasks,
      ecs:DescribeTasks, ec2:DescribeInstances, ssm:StartSession
    - The bastion's security group allowed to reach the eXist-db service
      on its port (already the case in this project's security groups)

.PARAMETER ProfileName
  AWS CLI profile to use (optional, falls back to the default credential
  chain / AWS_PROFILE env var).

.PARAMETER Region
  AWS region to use (optional, falls back to the profile's configured
  region).

.PARAMETER LocalPort
  Local port to listen on (default: 8086).
#>
param(
    [string]$ProfileName,
    [string]$Region,
    [int]$LocalPort = 8086
)

$ErrorActionPreference = "Stop"
$RemotePort = 8086

# See ecs-exec.ps1: native command exit codes are checked explicitly, since
# $ErrorActionPreference doesn't cover them.

function Get-AwsArgs {
    param([string[]]$CliArgs)
    $awsArgs = @()
    if ($ProfileName) { $awsArgs += @("--profile", $ProfileName) }
    if ($Region) { $awsArgs += @("--region", $Region) }
    $awsArgs += $CliArgs
    return $awsArgs
}

function Invoke-AwsCliJson {
    param([Parameter(ValueFromRemainingArguments = $true)][string[]]$CliArgs)
    $awsArgs = Get-AwsArgs -CliArgs $CliArgs
    $output = & aws @awsArgs
    if ($LASTEXITCODE -ne 0) {
        Write-Error "aws $($CliArgs -join ' ') failed (exit $LASTEXITCODE) - check your credentials/profile/SSO login (see the error above)."
    }
    return ($output | ConvertFrom-Json)
}

if (-not (Get-Command aws -ErrorAction SilentlyContinue)) {
    Write-Error "aws CLI not found on PATH."
}
if (-not (Get-Command session-manager-plugin -ErrorAction SilentlyContinue)) {
    Write-Error "session-manager-plugin not found on PATH - required for 'aws ssm start-session'. See: https://docs.aws.amazon.com/systems-manager/latest/userguide/session-manager-working-with-install-plugin.html"
}

function Select-One {
    param(
        [string]$Label,
        [string[]]$Items
    )

    if ($Items.Count -eq 0) {
        Write-Error "No $Label found."
    }

    if ($Items.Count -eq 1) {
        Write-Host "Only one $Label found, using it: $($Items[0])"
        return $Items[0]
    }

    $letters = [char[]]('a'..'z')
    if ($Items.Count -gt $letters.Count) {
        Write-Error "More than $($letters.Count) ${Label}s found - this tool only supports up to $($letters.Count) choices at once."
    }

    Write-Host "Select a ${Label}:"
    for ($i = 0; $i -lt $Items.Count; $i++) {
        Write-Host "  $($letters[$i])) $($Items[$i])"
    }

    while ($true) {
        $choice = (Read-Host ">").Trim().ToLowerInvariant()
        if ($choice.Length -eq 1) {
            $index = [Array]::IndexOf($letters, [char]$choice[0])
            if ($index -ge 0 -and $index -lt $Items.Count) {
                return $Items[$index]
            }
        }
        Write-Host "Invalid choice, try again."
    }
}

Write-Host "Looking up ECS clusters..."
$clusterArns = @((Invoke-AwsCliJson ecs list-clusters --output json).clusterArns)
$clusterArn = Select-One -Label "cluster" -Items $clusterArns
$clusterName = $clusterArn.Split('/')[-1]

Write-Host "Looking up eXist-db services in $clusterName..."
$serviceArns = @((Invoke-AwsCliJson ecs list-services --cluster $clusterArn --output json).serviceArns |
    Where-Object { $_.Split('/')[-1] -like '*existdb*' })
$serviceArn = Select-One -Label "eXist-db service" -Items $serviceArns
$serviceName = $serviceArn.Split('/')[-1]

Write-Host "Looking up running tasks for $serviceName..."
$taskArns = @((Invoke-AwsCliJson ecs list-tasks --cluster $clusterArn --service-name $serviceName --desired-status RUNNING --output json).taskArns)
$taskArn = Select-One -Label "task" -Items $taskArns
$taskId = $taskArn.Split('/')[-1]

$task = (Invoke-AwsCliJson ecs describe-tasks --cluster $clusterArn --tasks $taskArn --output json).tasks[0]
$taskIp = $task.containers[0].networkInterfaces[0].privateIpv4Address
if (-not $taskIp) {
    Write-Error "Task $taskId has no private IP yet (still provisioning?) - try again shortly."
}
Write-Host "eXist-db task $taskId is at $taskIp"

Write-Host "Looking up running bastion hosts..."
$bastionIds = @((Invoke-AwsCliJson ec2 describe-instances --filters "Name=tag:Name,Values=*bastion*" "Name=instance-state-name,Values=running" --query "Reservations[].Instances[].InstanceId" --output json))
$bastionId = Select-One -Label "bastion instance" -Items $bastionIds

Write-Host "Forwarding localhost:$LocalPort -> $bastionId -> ${taskIp}:$RemotePort (Ctrl+C to stop)"
Write-Host "eXist-db will be at http://localhost:$LocalPort/exist/"
# Shorthand (not JSON) --parameters syntax on purpose: Windows PowerShell
# 5.1 mangles embedded double quotes when passing JSON to native commands.
# Invoked directly (not captured) so session-manager-plugin keeps the TTY.
$sessionArgs = Get-AwsArgs -CliArgs @("ssm", "start-session", "--target", $bastionId, "--document-name", "AWS-StartPortForwardingSessionToRemoteHost", "--parameters", "host=$taskIp,portNumber=$RemotePort,localPortNumber=$LocalPort")
& aws @sessionArgs
if ($LASTEXITCODE -ne 0) {
    Write-Error "SSM port-forwarding session exited with code $LASTEXITCODE."
}
