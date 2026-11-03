# Network architecture

What the CDK app in [`infra/`](../../infra) builds in each application environment
(dev, qa, prod - identical layout, one AWS account and one VPC each).

![Sanakirja network architecture](network.svg)

The orange path is the only way into the VPC from the internet: HTTPS to the
Application Load Balancer, which forwards to the web tasks. No other component
has a public address, so everything else is reachable only from inside the VPC.
Outbound traffic from the application subnets leaves through the NAT gateways.

## Subnet tiers

| Tier | Type | Contents |
|---|---|---|
| public | public (internet gateway route) | ALB, NAT gateways |
| application | private, egress via NAT | web and eXist-db ECS Fargate services, EFS mount targets, bastion host |
| rds | private, isolated (no internet route) | Aurora PostgreSQL |

Each tier has one `/26` subnet per availability zone (2 AZs) inside the environment's `/23`.

## Security groups

| Target | Inbound allowed | From | Outbound |
|---|---|---|---|
| ALB | tcp/443 | 0.0.0.0/0 (IPv4) | all |
| Web (Fargate) | tcp/8080 | ALB, bastion | all (via NAT) |
| eXist-db (Fargate) | tcp/8086 | web, bastion | all (via NAT) |
| Aurora PostgreSQL | tcp/5432 | web, bastion | all (no route out of the subnet) |
| EFS | tcp/2049 | eXist-db, bastion | all |
| Bastion (EC2) | none | - (Session Manager only) | all (via NAT) |

Defined in [`infra/lib/security-groups.ts`](../../infra/lib/security-groups.ts).

## Notes

- The ALB has a single HTTPS listener on port 443. The web service owns the catch-all `/*` rule
  at the lowest priority, so other applications can be added behind the same ALB with
  higher-priority path rules.
- eXist-db has no ALB rule. The web tier reaches it through Cloud Map private DNS.
- The web tier connects to Aurora as a read-only database role.
- The bastion has no inbound rules. Administrators reach it through AWS Systems Manager
  Session Manager, which uses an outbound channel from the instance.
- There are no VPC endpoints, so calls to ECR, Secrets Manager, CloudWatch Logs, SSM and S3
  go out through the NAT gateways to the public AWS endpoints.
- Container images are pulled from ECR in a separate utility account. GitHub Actions builds
  and pushes images and deploys stacks through an OIDC-assumed IAM role.

Update the diagram when the network-facing parts of `infra/lib/` change. `network.svg` is
hand-written SVG and can be edited directly.
