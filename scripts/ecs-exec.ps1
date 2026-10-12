<#
.SYNOPSIS
  ECS Exec wizard: pick a cluster, service, task, and container, then open
  an ECS Exec shell into it.

.DESCRIPTION
  General-purpose - not tied to any specific project's cluster/service
  naming, so it works as-is against any account. Only shells out to the
  `aws` CLI (parsed via `--output json` + ConvertFrom-Json) - no AWS Tools
  for PowerShell module required, matching the bash version's "aws cli +
  Session Manager plugin only" dependency footprint.

  Requires:
    - AWS CLI v2 (for `aws ecs execute-command`)
    - Session Manager plugin on PATH:
      https://docs.aws.amazon.com/systems-manager/latest/userguide/session-manager-working-with-install-plugin.html
    - IAM permissions: ecs:ListClusters, ecs:ListServices, ecs:ListTasks,
      ecs:DescribeTasks, ecs:ExecuteCommand, ssm:StartSession
    - ECS Exec enabled on the target service (enableExecuteCommand: true)

.PARAMETER ProfileName
  AWS CLI profile to use (optional, falls back to the default credential
  chain / AWS_PROFILE env var).

.PARAMETER Region
  AWS region to use (optional, falls back to the profile's configured
  region).

.PARAMETER Command
  Command to run in the container (default: /bin/sh).
#>
param(
    [string]$ProfileName,
    [string]$Region,
    [string]$Command = "/bin/sh"
)

$ErrorActionPreference = "Stop"

# $ErrorActionPreference only governs PowerShell's own error records - it
# does NOT turn a non-zero exit code from a native command (like `aws`)
# into a terminating error. So a failure such as an expired SSO token
# would otherwise print the aws CLI's own error and then carry on with
# empty/garbage data. Invoke-AwsCliJson below checks $LASTEXITCODE itself
# and fails loudly instead.

function Get-AwsArgs {
    param([string[]]$CliArgs)
    $awsArgs = @()
    if ($ProfileName) { $awsArgs += @("--profile", $ProfileName) }
    if ($Region) { $awsArgs += @("--region", $Region) }
    $awsArgs += $CliArgs
    return $awsArgs
}

# For the four listing steps only (JSON, non-interactive) - captures
# output so it can check $LASTEXITCODE. Deliberately NOT used for the
# final execute-command call: capturing an interactive command's output
# into a variable breaks the real TTY pass-through session-manager-plugin
# needs, so that call invokes `aws` directly instead (see bottom of file).
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
    Write-Error "session-manager-plugin not found on PATH - required for 'aws ecs execute-command --interactive'. See: https://docs.aws.amazon.com/systems-manager/latest/userguide/session-manager-working-with-install-plugin.html"
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

Write-Host "Looking up services in $clusterName..."
$serviceArns = @((Invoke-AwsCliJson ecs list-services --cluster $clusterArn --output json).serviceArns)
$serviceArn = Select-One -Label "service" -Items $serviceArns
$serviceName = $serviceArn.Split('/')[-1]

Write-Host "Looking up running tasks for $serviceName..."
$taskArns = @((Invoke-AwsCliJson ecs list-tasks --cluster $clusterArn --service-name $serviceName --desired-status RUNNING --output json).taskArns)
$taskArn = Select-One -Label "task" -Items $taskArns
$taskId = $taskArn.Split('/')[-1]

Write-Host "Looking up containers in task $taskId..."
$containers = @((Invoke-AwsCliJson ecs describe-tasks --cluster $clusterArn --tasks $taskArn --output json).tasks[0].containers | ForEach-Object { $_.name })
$container = Select-One -Label "container" -Items $containers

Write-Host "Opening ECS Exec shell: cluster=$clusterName task=$taskId container=$container command=$Command"
$execArgs = Get-AwsArgs -CliArgs @("ecs", "execute-command", "--cluster", $clusterArn, "--task", $taskArn, "--container", $container, "--interactive", "--command", $Command)
& aws @execArgs
if ($LASTEXITCODE -ne 0) {
    Write-Error "ECS Exec session exited with code $LASTEXITCODE."
}
