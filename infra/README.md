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

## AWS vault / SSO

`aws-vault exec <target-aws-profile> -- npx cdk <command> -c environment=<dev/qa/prod/utility> -c webRevision=<tag> -c existdbRevision=<tag>`, or `aws sso login --sso-session oph-org-sso --profile <target-account>` first.

`webRevision`/`existdbRevision` are each service's own image tag (`WebEcsService`/`ExistDbEcsService` each read their own) and are required on every deploy - there's no default. They're separate context values, not one shared `revision`, because `build-and-push.yml` builds/pushes each image independently (path-filtered per service) - redeploying `sanakirja-web` with a new tag should never require `sanakirja-existdb` to also have a matching tag pushed, and vice versa. Both are tagged `ga-<GitHub Actions run number>` by `build-and-push.yml` (ECR repos are `imageTagMutability: IMMUTABLE`, so the tag must be new each push).

## Common commands

* `npx cdk deploy -c environment=<dev/qa/prod/utility> -c webRevision=<tag> -c existdbRevision=<tag> --all` - deploy all stacks for an environment
* `npx cdk destroy -c environment=<dev/qa/prod/utility> --all` - destroy all stacks (empty S3 buckets manually first)
* `npx cdk diff -c environment=<dev/qa/prod/utility> -c webRevision=<tag> -c existdbRevision=<tag>` - compare deployed vs. current code
* `npm run build` - compile TypeScript
* `npm test` - run jest
* `npx cdk synth` - emit the synthesized CloudFormation templates
