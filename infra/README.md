# Sanakirja AWS infrastructure

AWS CDK (TypeScript) infrastructure for the Sanakirja project: one stack per
CloudFormation stack under `lib/`, wired together per environment in `bin/infra.ts`,
with non-sensitive per-environment config in `environments/<env>.json` and secrets
in AWS Secrets Manager / Parameter Store.

## Before the first real deploy

A few things are still placeholders and must be filled in before `dev`/`qa`/`prod` can
actually deploy:

- **`environments/{dev,qa,prod}.json` → `aws.domain`**: all three now have real
  domains (`<env>.sanakirja.testiopintopolku.fi`, OPH's test domain), but only
  `dev`'s `HostedZoneStack` is deployed so far - enable it for qa/prod individually
  when ready. `AlbStack` (ACM certificate DNS validation) won't work for any
  environment until that environment's zone is actually delegated - see
  `ParentZoneStack` below.
- **`ParentZoneStack`** (dev account only) - the top-level `sanakirja.testiopintopolku.fi`
  zone already exists outside this CDK app (see "Account IDs & other secret
  identifiers" below for its id) and stays that way; this stack only imports it
  and holds NS records delegating down to each environment's own
  `HostedZoneStack` zone. dev's delegation is wired automatically (same account,
  plain cross-stack reference); qa's and prod's need their own `HostedZoneStack`
  deployed first, then their real NS values filled into
  `environments/{qa,prod}.json` → `aws.delegatedNameServers` (empty array = not
  deployed yet, so no delegation record is created).
- ~~`environments/utility.json` → `ecr.project_ou`~~ - done: the OU id is set via CDK
  context (`projectOu`), not this file - see "Account IDs & other secret identifiers"
  below.
- **Bastion SSH ingress**: `security-groups.ts`'s `bastionSecurityGroup` currently has no
  inbound rule at all (Session Manager doesn't need one). Once OPH admins provide the
  allowed IP list, add a restricted `ec2.Port.tcp(22)` ingress rule there - keep it as
  strict as possible.
- **Bastion AMI**: `bastion-stack.ts` still uses the default `ec2.BastionHostLinux` image.
  Once the Packer pipeline for the bastion AMI exists, wire its output in here instead.

## Stack overview

Account IDs, contact emails, the AWS Organizations org id, and CIDR allocations
are tracked privately outside this repository - ask the team for them rather
than committing them here. The org id and each environment's VPC CIDR are
supplied via CDK context (`orgId`, `vpcCidr`) - see "Account IDs & other secret
identifiers" below.

Per environment (`dev`/`qa`/`prod`):
- `VpcStack` - one VPC per environment, `/23` split into `/26` public/application/rds
  subnets - 3 tiers x 2 AZs uses 6 of the 8 available `/26`s, leaving headroom for
  a future tier or a third AZ.
- `SecurityGroupStack`, `KmsStack`, `SecretManagerStack` - shared plumbing.
- `HostedZoneStack`, `AlbStack` - public Route 53 zone + ALB. The ALB is deliberately
  multi-app-ready: each `EcsServiceStack` registers its own path-pattern listener rule,
  Sanakirja's web tier isn't the only thing this ALB will ever route to.
  Delegated from the shared `ParentZoneStack` (dev account only, see above).
- `BastionStack` - Session Manager only, strict security group (see TODOs above).
- `AuroraCommonStack` + `AuroraDatabaseStack` - PostgreSQL for the search-word index,
  delivered as Aurora Serverless v2 (RDS/Aurora Postgres engine family) so capacity
  tracks the small, static workload.
- `FargateClusterStack`, `NamespaceStack` - shared ECS Fargate cluster + Cloud Map
  namespace for service-to-service DNS.
- `EfsStack` - persistent storage for eXist-db's data directory (Fargate can't mount
  EBS), with a daily AWS Backup plan, 7-day retention.
- `EcsServiceStack` x2, both on Fargate `CpuArchitecture.ARM64` (Graviton - cheaper,
  and nothing in either image needs x86 specifically):
  - `sanakirja-web` - Apache/nginx + php-fpm (web server choice still open), exposed
    on the ALB.
  - `sanakirja-existdb` - eXist-db 6.x, internal only (reached via Cloud Map, no ALB
    listener rule), with the EFS volume mounted at `/exist/data`.

  eXist-db's admin password is only ever applied **once**, on first boot (the
  entrypoint guards it with a marker file on the persistent EFS volume, so later
  container restarts skip it). Rotating `/service/existdb/ADMIN_PASS` in Secrets
  Manager after that first boot does **not** change the running database's actual
  password - the live container keeps whatever value it had at launch, and the
  two silently drift apart. If the secret needs to change on an already-initialized
  environment, the new password has to also be pushed into eXist-db by hand
  (log in with the still-active old password and update it there), not just
  rotated in Secrets Manager.

Utility account:
- `UtilityStack` - GitHub Actions OIDC provider + deployment role.
- `EcrStack` x2 - one repository per service (`sanakirja-web`, `sanakirja-existdb`).

## Account IDs & other secret identifiers

AWS account IDs, and identifiers derived from or scoped to them (an AWS
Organizations org/OU id, an IAM role ARN), are treated as secret and must never be
hardcoded into a file in this repo (it's public) - always read from CDK context
instead. The already-existing `sanakirja.testiopintopolku.fi` zone's id
(`ParentZoneStack` imports it, never creates it) gets the same treatment, so
that real Route 53 resource id never appears as a literal here either. Each
environment's VPC CIDR (admin-mandated per-environment network design) and own
domain (today a `*.sanakirja.testiopintopolku.fi` test subdomain, due to change
to a real public domain at launch) get the same treatment too, since neither is
meant to be committed as a literal:

| Context key | Used by | Purpose |
|---|---|---|
| `utilityAccountId` | `EcsServiceStack` (once re-enabled) | builds the cross-account ECR image ARN |
| `projectOu` | `EcrStack` x2 | AWS Organizations OU id scoping who can pull from the ECR repos |
| `parentZoneId` | `ParentZoneStack` (dev only) | id of the already-existing `sanakirja.testiopintopolku.fi` zone |
| `orgId` | `EcrStack` x2 | AWS Organizations org id (distinct from the OU id above) scoping who can pull from the ECR repos |
| `vpcCidr` | `VpcStack` (dev/qa/prod) | the environment's `/23` CIDR block - a different value per environment. `utility` has no `VpcStack` today, but its `AWS_SANAKIRJA_VPC_CIDR` GitHub Environment secret is still set (`10.8.6.0/23`) so the value isn't lost if one is added later |
| `domain` | `HostedZoneStack`, `AlbStack` (dev/qa/prod) | the environment's own domain - a different value per environment, expected to change at launch |

- **Local CLI**: pass `-c utilityAccountId=<id> -c projectOu=<id> -c parentZoneId=<id> -c orgId=<id> -c vpcCidr=<cidr> -c domain=<domain>`
  on the command line. `utilityAccountId`/`projectOu`/`parentZoneId`/`orgId` are
  the same across every environment, so it's convenient to add them to
  `infra/cdk.context.json` once (already gitignored) instead of retyping them
  every run: `{ "utilityAccountId": "<id>", "projectOu": "<id>", "parentZoneId": "<id>", "orgId": "<id>" }`.
  `vpcCidr` and `domain` differ per environment, so pass them explicitly on the
  command line each time rather than putting one value in `cdk.context.json`.
- **CI (GitHub Actions)**: `deploy-infra.yml` passes them from the repository
  secrets `AWS_UTILITY_ACCOUNT_ID`, `AWS_SANAKIRJA_PROJECT_OU`,
  `AWS_SANAKIRJA_PARENT_ZONE_ID` and `AWS_SANAKIRJA_ORG_ID`, plus the
  environment-scoped secrets `AWS_SANAKIRJA_VPC_CIDR` and `AWS_SANAKIRJA_DOMAIN`
  (one secret name each, a different value set per GitHub Environment - same
  pattern as `AWS_DEPLOY_ROLE_ARN` below).

The same treatment applies to the GitHub Actions OIDC deployment role ARNs
(`github-actions-deployment-role-<env>` / `utility-github-actions-deployment-role`)
- each contains an account ID, so none of them can live in the workflow file either.
Instead, `deploy-infra.yml` reads `secrets.AWS_DEPLOY_ROLE_ARN` through a GitHub
**Environment** (Settings > Environments) matching each `-c environment=` value
(`dev`/`qa`/`prod`/`utility`) - same secret name in every environment, a different
ARN value per environment, so the workflow YAML itself never names an account.

Ask whoever holds these values for what to put into `AWS_UTILITY_ACCOUNT_ID`,
`AWS_SANAKIRJA_PROJECT_OU`, `AWS_SANAKIRJA_PARENT_ZONE_ID`, and each
environment's `AWS_DEPLOY_ROLE_ARN`.

## Deploying through GitHub Actions

The normal way to deploy. Two workflows in `.github/workflows/`, both run from
`main`: one builds container images, the other deploys an environment. Building
never deploys anything, and nothing deploys automatically - every deploy is a
manual workflow run.

### 1. Build images - `build-and-push.yml`

- **Runs on** a push to `main` that touches `services/web/**`,
  `services/existdb/**` or the workflow file itself, or manually (Actions →
  "Build and push service images" → Run workflow).
- **Builds** both images on every run, whichever folder changed.
- **Pushes** `linux/arm64` images (the ECS task definitions are
  `CpuArchitecture.ARM64`) to the `sanakirja-web` / `sanakirja-existdb` ECR
  repositories in the utility account.
- **Tags** both images `ga-<workflow run number>`. The repositories are
  immutable, so a tag always refers to exactly one image.

Because both images are always built together, the newest tag is the same in
both repositories. Look it up (utility account):

```
aws ecr describe-images --region eu-west-1 --repository-name sanakirja-web \
  --query 'sort_by(imageDetails,&imagePushedAt)[-1].imageTags' --output text
```

Older `manual-*` / `ks-app-*` tags in
the repositories are x86_64 images from before the pipeline existed - they do
not run on the current task definitions, don't deploy them.

### 2. Deploy an environment - `deploy-infra.yml`

Actions → "Deploy infra" → Run workflow, or from the CLI:

```
gh workflow run deploy-infra.yml -f environment=dev -f web_revision=ga-2 -f existdb_revision=ga-2
```

| Input | Meaning |
|---|---|
| `environment` | `dev` / `qa` / `prod` / `utility`. Selects the GitHub Environment of the same name, which holds that account's `AWS_DEPLOY_ROLE_ARN` and the per-environment secrets (see "Account IDs & other secret identifiers") |
| `web_revision` | `sanakirja-web` image tag to run |
| `existdb_revision` | `sanakirja-existdb` image tag to run |

The workflow runs `cdk deploy --all` for the chosen environment:

- **Every stack** in the environment is deployed, in dependency order. Stacks
  that don't exist yet are created, unchanged ones are a quick no-op.
- **Both revisions are required** for `dev`/`qa`/`prod`. Normally both are the
  same newest tag. To update just web without restarting eXist-db, pass the
  tag existdb is already running as `existdb_revision`. They aren't used for
  `utility`.
- **Changing a tag** creates a new task definition and replaces the running
  task. Web starts the new task first and drains the old one once the ALB
  health check passes (no downtime). eXist-db stops the old task first and then
  starts the new one (about a minute without eXist-db) - its EFS data directory
  can only be held by one task at a time.
- **A task that never becomes healthy** trips the ECS deployment circuit
  breaker, which rolls the service back to the previous task definition and
  fails the workflow run.

### Which build runs where

There is no file that pins an environment to a build - the tags are chosen at
deploy time. The usual flow:

1. **dev**: deploy the newest tag of each repository after a build.
2. **qa**: deploy the tags that were verified in dev.
3. **prod**: deploy the tags that were verified in qa.

To see what an environment is running, check the image of its ECS service's
current task definition, or the inputs of the latest successful "Deploy infra"
run for that environment. To roll back, deploy the previous tags again.

### First deploy of a new environment

`AlbStack`'s ACM certificate is DNS-validated in the environment's own hosted
zone, which only works once that zone is delegated from the parent
`sanakirja.testiopintopolku.fi` zone (held in the dev account). A first
`--all` deploy into a fresh environment therefore stalls on the certificate
and fails. Do it in this order instead:

1. Deploy only `HostedZoneStack` for the new environment, locally (the
   workflow always deploys everything):
   `npx cdk deploy -c environment=<env> ... HostedZoneStack`
2. Copy the new zone's four name servers into
   `environments/<env>.json` → `aws.delegatedNameServers`, and commit.
3. Deploy `dev` (its `ParentZoneStack` creates the delegation record).
4. Check the delegation resolves: `dig +short NS <env domain>`.
5. Deploy the new environment with the workflow as usual.

The AWS Chatbot Slack authorization (needed by `MonitorStack`) and the
`/monitor/slack_*` SSM parameters must also exist in the account before its
first deploy - both are one-time manual steps per account.

## AWS vault / SSO

For deploying from your own machine instead of GitHub Actions (e.g. the
`HostedZoneStack`-only step above, or `cdk diff`):

`aws-vault exec <target-aws-profile> -- npx cdk <command> -c environment=<dev/qa/prod/utility> -c webRevision=<tag> -c existdbRevision=<tag>`, or `aws sso login --sso-session oph-org-sso --profile <target-account>` first.

`webRevision`/`existdbRevision` are each service's own image tag (`WebEcsService`/`ExistDbEcsService` each read their own) and are required on every deploy - there's no default. They're separate context values, not one shared `revision`, so one service can be redeployed with a new tag while the other keeps running its current one (e.g. updating web without restarting eXist-db). Both are tagged `ga-<GitHub Actions run number>` by `build-and-push.yml` (ECR repos are `imageTagMutability: IMMUTABLE`, so the tag must be new each push).

## Common commands

* `npx cdk deploy -c environment=<dev/qa/prod/utility> -c webRevision=<tag> -c existdbRevision=<tag> --all` - deploy all stacks for an environment
* `npx cdk destroy -c environment=<dev/qa/prod/utility> --all` - destroy all stacks (empty S3 buckets manually first)
* `npx cdk diff -c environment=<dev/qa/prod/utility> -c webRevision=<tag> -c existdbRevision=<tag>` - compare deployed vs. current code
* `npm run build` - compile TypeScript
* `npm test` - run jest
* `npx cdk synth` - emit the synthesized CloudFormation templates
