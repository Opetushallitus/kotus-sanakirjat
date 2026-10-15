#!/usr/bin/env node
import 'source-map-support/register'
import * as cdk from 'aws-cdk-lib'
import * as utility from '../environments/utility.json'
import * as dev from '../environments/dev.json'
import * as qa from '../environments/qa.json'
import * as prod from '../environments/prod.json'
import { VpcStack } from '../lib/vpc-stack'
import { SecurityGroupStack } from '../lib/security-groups'
import { AuroraCommonStack } from '../lib/aurora-serverless-common'
import { AuroraDatabaseStack } from '../lib/aurora-serverless-database'
import { AlbStack } from '../lib/alb-stack'
import { KmsStack } from '../lib/kms-stack'
import { FargateClusterStack } from '../lib/fargate-cluster-stack'
import { EcsServiceStack } from '../lib/ecs-service'
import { EcrStack } from '../lib/ecr-stack'
import { CpuArchitecture } from 'aws-cdk-lib/aws-ecs'
import { BastionStack } from '../lib/bastion-stack'
import { TransferBucketStack } from '../lib/transfer-bucket-stack'
import { SecretManagerStack, SEARCH_INDEX_READONLY_USERNAME } from '../lib/secrets-manager-stack'
import { HostedZoneStack } from '../lib/hosted-zone-stack'
import { ParentZoneStack } from '../lib/parent-zone-stack'
import { NamespaceStack } from '../lib/namespaceStack'
import { EfsStack } from '../lib/efs-stack'
import { GithubActionsStack } from '../lib/githubActionsStack'
import { UtilityStack } from '../lib/utility-stack'
import { MonitorStack } from '../lib/monitor-stack'

const app = new cdk.App()

// Load up configuration for the environment
const environmentName: string = app.node.tryGetContext('environment')
const envEU = { region: 'eu-west-1' }
// The shared parent zone that dev/qa/prod each delegate their own
// <env>.sanakirja.testiopintopolku.fi zone from - see ParentZoneStack (dev only).
const PARENT_DOMAIN = 'sanakirja.testiopintopolku.fi'
// SSM context lookups (MonitorStack's slack_channel_id/slack_workspace_id
// parameters) need an explicit account, not just a region.
const envEUAccount = { account: process.env.CDK_DEFAULT_ACCOUNT, region: 'eu-west-1' }

// The web and existdb images are built/pushed independently (build-and-push.yml
// path-filters each one), so each service tracks its own revision rather than
// sharing one - deploying a new web image never requires existdb to also have
// a matching tag pushed, and vice versa.
function getWebRevisionFromEnv() {
  if (app.node.tryGetContext('webRevision')) {
    return app.node.tryGetContext('webRevision')
  }
  throw new Error('Missing webRevision context value')
}

function getExistDbRevisionFromEnv() {
  if (app.node.tryGetContext('existdbRevision')) {
    return app.node.tryGetContext('existdbRevision')
  }
  throw new Error('Missing existdbRevision context value')
}

// AWS account IDs are treated as secret and must never be hardcoded into a file
// that goes into this repo. Supply it with `-c utilityAccountId=<id>`:
// locally, persist it in infra/cdk.context.json (gitignored) so it's not retyped
// every run; in CI, pass it from a GitHub Actions secret. See infra/README.md
// "Account IDs" for the exact value and setup.
function getUtilityAccountIdFromContext() {
  if (app.node.tryGetContext('utilityAccountId')) {
    return app.node.tryGetContext('utilityAccountId')
  }
  throw new Error('Missing utilityAccountId context value - see infra/README.md "Account IDs"')
}

// Same treatment as utilityAccountId above - the AWS Organizations OU id is also
// kept out of this repo. Supply it with `-c projectOu=<id>`.
function getProjectOuFromContext() {
  if (app.node.tryGetContext('projectOu')) {
    return app.node.tryGetContext('projectOu')
  }
  throw new Error('Missing projectOu context value - see infra/README.md "Account IDs"')
}

// The already-existing sanakirja.testiopintopolku.fi zone's id (ParentZoneStack
// imports it, never creates it - see the dev branch below) - same context
// treatment as utilityAccountId/projectOu above. Supply it with
// `-c parentZoneId=<id>`.
function getParentZoneIdFromContext() {
  if (app.node.tryGetContext('parentZoneId')) {
    return app.node.tryGetContext('parentZoneId')
  }
  throw new Error('Missing parentZoneId context value - see infra/README.md "Account IDs"')
}

// Same treatment as the ids above - the AWS Organizations org id (distinct from
// the project OU id) is also kept out of this repo. Supply it with `-c orgId=<id>`.
function getOrgIdFromContext() {
  if (app.node.tryGetContext('orgId')) {
    return app.node.tryGetContext('orgId')
  }
  throw new Error('Missing orgId context value - see infra/README.md "Account IDs"')
}

// Each environment's VPC CIDR is admin-mandated per-environment network design,
// tracked privately rather than as a literal in this public repo - same context
// treatment as the ids above. Supply it with `-c vpcCidr=<cidr>`.
function getVpcCidrFromContext() {
  if (app.node.tryGetContext('vpcCidr')) {
    return app.node.tryGetContext('vpcCidr')
  }
  throw new Error('Missing vpcCidr context value - see infra/README.md "Account IDs"')
}

// Each environment's own domain (today a *.sanakirja.testiopintopolku.fi test
// subdomain, but due to change to a real public domain at launch) - kept out of
// this repo the same way, rather than hardcoded per-environment. Supply it with
// `-c domain=<domain>`.
function getDomainFromContext() {
  if (app.node.tryGetContext('domain')) {
    return app.node.tryGetContext('domain')
  }
  throw new Error('Missing domain context value - see infra/README.md "Account IDs"')
}

// Allow any in this case, since we don't want to explicitely type json data
/* eslint-disable  @typescript-eslint/no-explicit-any */
let environmentConfig: any

if (environmentName === 'utility') {
  environmentConfig = utility
} else if (environmentName === 'dev') {
  environmentConfig = dev
} else if (environmentName === 'qa') {
  environmentConfig = qa
} else if (environmentName === 'prod') {
  environmentConfig = prod
} else {
  console.error(
    'You must define a valid environment name in CDK context! Valid environment names are dev, qa, prod and utility'
  )
  process.exit(1)
}

// dev, qa & prod account resources..
if (environmentName === 'dev' || environmentName === 'qa' || environmentName === 'prod') {

  new GithubActionsStack(app, 'GithubActionsStack', {
    env: envEU,
    environment: environmentName,
    repositoryRegex: 'kotus-sanakirjat'
  })

  // MonitorStack does a live SSM lookup for /monitor/slack_channel_id and
  // /monitor/slack_workspace_id during `cdk synth` itself (not just deploy) - both
  // must exist in this account already (set manually, not by any CDK stack - ask
  // the team if they're missing). No equivalent for utility - MonitorStack isn't
  // deployed there.
  const Monitor = new MonitorStack(app, 'MonitorStack', {
    env: envEUAccount,
    slackChannelName: `valvonta-sanakirjat-${environmentName}`,
    environment: environmentName,
  })

  // Remember to update KMS key removal policy
  const Kms = new KmsStack(app, 'KmsStack', {
    env: envEU,
    stackName: `${environmentName}-kms`,
    environment: environmentName
  })

  const Secrets = new SecretManagerStack(app, 'SecretManagerStack', {
    env: envEU,
    stackName: `${environmentName}-secrets`,
    kmsKey: Kms.secretsManagerKey
  })

  const Network = new VpcStack(app, 'VpcStack', {
    env: envEU,
    stackName: `${environmentName}-vpc`,
    vpc_cidr: getVpcCidrFromContext(),
    availability_zones: environmentConfig.aws.availability_zones
  })

  const SecurityGroups = new SecurityGroupStack(app, 'SecurityGroupStack', {
    env: envEU,
    stackName: `${environmentName}-security-groups`,
    vpc: Network.vpc
  })

  const TransferBucket = new TransferBucketStack(app, 'TransferBucketStack', {
    env: envEU,
    stackName: `${environmentName}-database-transfer-bucket`,
    environment: environmentName,
    kmsKey: Kms.s3KmsKey
  })

  const Namespace = new NamespaceStack(app, 'NameSpaceStack', Network.vpc, {
    env: envEU,
    environment: environmentName,
    projectName: 'sanakirja',
  })

  new BastionStack(app, 'BastionStack', {
    env: envEU,
    stackName: `${environmentName}-bastion`,
    vpc: Network.vpc,
    securityGroup: SecurityGroups.bastionSecurityGroup,
    kmsKey: Kms.ebsKmsKey,
    environment: environmentName,
    transferBucket: TransferBucket.bucket,
    existDbHost: `sanakirja-existdb.${Namespace.privateDnsNamespace.namespaceName}`
  })

  const AuroraCommons = new AuroraCommonStack(app, 'AuroraCommonStack', {
    env: envEU,
    stackName: `${environmentName}-aurora-common`,
    vpc: Network.vpc
  })

  const SearchIndexAurora = new AuroraDatabaseStack(app, 'SearchIndexAuroraStack', {
    env: envEU,
    stackName: `${environmentName}-search-index-aurora`,
    auroraVersion: environmentConfig.aurora_databases.search_index.version,
    environment: environmentName,
    clusterName: 'search-index',
    vpc: Network.vpc,
    securityGroup: SecurityGroups.searchIndexAuroraSecurityGroup,
    performanceInsights: environmentConfig.aurora_databases.search_index.performance_insights,
    minSizeAcu: environmentConfig.aurora_databases.search_index.min_size_acu,
    maxSizeAcu: environmentConfig.aurora_databases.search_index.max_size_acu,
    kmsKey: Kms.rdsKmsKey,
    auroraDbPassword: Secrets.searchIndexAuroraPassword,
    subnetGroup: AuroraCommons.auroraSubnetGroup,
    alarmSnsTopic: Monitor.topic,
  })

  const FargateCluster = new FargateClusterStack(app, 'FargateClusterStack', {
    env: envEU,
    stackName: `${environmentName}-fargate-cluster`,
    environment: environmentName,
    vpc: Network.vpc,
    logGroupKmsKey: Kms.cloudwatchLogsKmsKey
  })

  const Efs = new EfsStack(app, 'EfsStack', {
    env: envEU,
    stackName: `${environmentName}-existdb-efs`,
    environment: environmentName,
    vpc: Network.vpc,
    securityGroup: SecurityGroups.efsSecurityGroup,
    accessPointPath: '/existdb-data',
    throughputMode: environmentConfig.EFS.throughputMode,
    kmsKey: Kms.efsKmsKey
  })

  // Each environment gets its own public hosted zone, ALB and ECS services
  // once its zone/delegation/images are ready - ParentZoneStack (below) stays
  // dev-account-only since it's the single stack that owns the shared parent
  // zone and fans delegation out to every environment.
  {
    const HostedZone = new HostedZoneStack(app, 'HostedZoneStack', {
      env: envEU,
      stackName: `${environmentName}-hosted-zone`,
      domain: getDomainFromContext(),
      vpc: Network.vpc
    })

    // sanakirja.testiopintopolku.fi itself already exists (created manually, in
    // the dev account, before this stack existed) and stays that way -
    // ParentZoneStack never creates, owns or modifies the zone. It only imports
    // it by id/name and adds NS delegation records into it, down to each
    // environment's own zone above. dev's NS values are known immediately
    // (same account/app, plain cross-stack reference); qa's and prod's aren't
    // known until their own HostedZoneStacks are deployed in their own
    // accounts - fill in environments/{qa,prod}.json's aws.delegatedNameServers
    // with the real values once that happens (empty means "not deployed yet",
    // so no delegation record is created for it).
    if (environmentName === 'dev') {
      new ParentZoneStack(app, 'ParentZoneStack', {
        env: envEU,
        stackName: 'sanakirja-parent-zone',
        domain: PARENT_DOMAIN,
        parentZoneId: getParentZoneIdFromContext(),
        delegations: [
          { subdomain: 'dev', nameServers: HostedZone.publicHostedZone.hostedZoneNameServers as string[] },
          { subdomain: 'qa', nameServers: qa.aws.delegatedNameServers },
          { subdomain: 'prod', nameServers: prod.aws.delegatedNameServers }
        ]
      })
    }

    const webRevision = getWebRevisionFromEnv()
    const existDbRevision = getExistDbRevisionFromEnv()
    const domain = getDomainFromContext()

    const Alb = new AlbStack(app, 'AlbStack', {
      env: envEU,
      environment: environmentName,
      stackName: `${environmentName}-alb`,
      vpc: Network.vpc,
      securityGroupId: SecurityGroups.albSecurityGroup.securityGroupId,
      domain: domain,
      publicHostedZone: HostedZone.publicHostedZone,
      alarmSnsTopic: Monitor.topic
    })

    // eXist-db: internal only - reached by the web tier over Cloud Map service
    // discovery, not exposed on the public ALB.
    new EcsServiceStack(app, 'ExistDbEcsService', {
      env: envEU,
      stackName: `${environmentName}-existdb-service`,
      serviceName: 'sanakirja-existdb',
      environment: environmentName,
      cluster: FargateCluster.fargateCluster,
      vpc: Network.vpc,
      securityGroup: SecurityGroups.existDbServiceSecurityGroup,
      containerPort: 8086, // eXist-db's real Jetty port, not 8080
      revision: existDbRevision,
      allowEcsExec: environmentConfig.services.existdb.allow_ecs_exec,
      taskCpu: environmentConfig.services.existdb.cpu_limit,
      taskMemory: environmentConfig.services.existdb.memory_limit,
      minimumCount: environmentConfig.services.existdb.min_count,
      maximumCount: environmentConfig.services.existdb.max_count,
      cpuArchitecture: CpuArchitecture.ARM64,
      env_vars: environmentConfig.services.existdb.env_vars,
      parameter_store_secrets: [],
      secrets_manager_secrets: [Secrets.secrets.EXISTDB_ADMIN_PASS],
      utilityAccountId: getUtilityAccountIdFromContext(),
      healthCheckGracePeriod: 180,
      healthCheckInterval: 5,
      healthCheckTimeout: 2,
      iAmPolicyStatements: [Efs.efsPolicyStatement],
      privateDnsNamespace: Namespace.privateDnsNamespace,
      alarmSnsTopic: Monitor.topic,
      // eXist-db holds an exclusive lock file in its EFS data directory: a second
      // task started alongside (rolling deploy) fails to start with "directory
      // seems to be locked by another database instance" - and its process keeps
      // running without ever listening on 8086. Hence one task at a time (also
      // why existdb max_count must stay 1), plus a health check so such a task
      // gets replaced instead of sitting there as RUNNING.
      singleWriter: true,
      containerHealthCheck: {
        command: ['CMD-SHELL', 'curl -fsS http://localhost:8086/exist/status || exit 1'],
        interval: cdk.Duration.seconds(30),
        timeout: cdk.Duration.seconds(5),
        retries: 5,
        startPeriod: cdk.Duration.seconds(180),
      },
      efs: {
        volume: {
          name: 'existdb-data',
          efsVolumeConfiguration: {
            fileSystemId: Efs.fileSystemId,
            transitEncryption: 'ENABLED',
            authorizationConfig: {
              accessPointId: Efs.accessPoint.accessPointId,
              iam: 'ENABLED'
            }
          }
        },
        mountPoint: {
          sourceVolume: 'existdb-data',
          containerPath: '/exist/data', // from bastion: sudo mount -t efs -o tls,iam <fileSystemId>:/ /mnt/existdb-data
          readOnly: false
        }
      }
    })

    // Web tier. Sole ALB service today, but the ALB/listener are shared
    // infrastructure: this claims the lowest-priority catch-all rule ('/*') so
    // future services behind the same ALB can register more specific,
    // higher-priority path patterns without touching this stack.
    new EcsServiceStack(app, 'WebEcsService', {
      env: envEU,
      stackName: `${environmentName}-web-service`,
      serviceName: 'sanakirja-web',
      environment: environmentName,
      cluster: FargateCluster.fargateCluster,
      vpc: Network.vpc,
      securityGroup: SecurityGroups.webServiceSecurityGroup,
      containerPort: 8080,
      revision: webRevision,
      allowEcsExec: environmentConfig.services.web.allow_ecs_exec,
      taskCpu: environmentConfig.services.web.cpu_limit,
      taskMemory: environmentConfig.services.web.memory_limit,
      minimumCount: environmentConfig.services.web.min_count,
      maximumCount: environmentConfig.services.web.max_count,
      cpuArchitecture: CpuArchitecture.ARM64,
      env_vars: {
        ...environmentConfig.services.web.env_vars,
        ...{
          POSTGRESQL_HOST: SearchIndexAurora.endPoint.hostname,
          POSTGRESQL_PORT: SearchIndexAurora.endPoint.port,
          PG_USER: SEARCH_INDEX_READONLY_USERNAME,
          EXISTDB_HOST: `sanakirja-existdb.${Namespace.privateDnsNamespace.namespaceName}`,
          EXISTDB_PORT: '8086'
        }
      },
      parameter_store_secrets: [],
      secrets_manager_secrets: [Secrets.secrets.PG_PASS],
      utilityAccountId: getUtilityAccountIdFromContext(),
      listener: Alb.albListener,
      listenerPathPatterns: ['/*'],
      healthCheckPath: '/health',
      healthCheckGracePeriod: 180,
      healthCheckInterval: 5,
      healthCheckTimeout: 2,
      albPriority: 49000,
      privateDnsNamespace: Namespace.privateDnsNamespace,
      alarmSnsTopic: Monitor.topic,
    })
  }

} else if (environmentName === 'utility') {
  const Utility = new UtilityStack(app, 'UtilityStack', {
    env: envEU,
    stackName: `${environmentName}-utility`,
    repositoryRegex: 'kotus-sanakirjat'
  })

  // ECR stacks use an AWS Organizations Organizational Unit for sharing the ECR
  // repositories between accounts - see getProjectOuFromContext() above for how
  // that id is supplied (treated as secret, same as utilityAccountId).
  new EcrStack(app, 'WebEcrStack', {
    env: envEU,
    stackName: 'sanakirja-web-ecr',
    serviceName: 'sanakirja-web',
    orgId: getOrgIdFromContext(),
    projectAwsOrgOrganizationalUnit: getProjectOuFromContext(),
    githubActionsDeploymentRole: Utility.githubActionsDeploymentRole
  })
  new EcrStack(app, 'ExistDbEcrStack', {
    env: envEU,
    stackName: 'sanakirja-existdb-ecr',
    serviceName: 'sanakirja-existdb',
    orgId: getOrgIdFromContext(),
    projectAwsOrgOrganizationalUnit: getProjectOuFromContext(),
    githubActionsDeploymentRole: Utility.githubActionsDeploymentRole
  })
}
