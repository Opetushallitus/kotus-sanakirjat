import * as cdk from 'aws-cdk-lib'
import { Construct } from 'constructs'
import * as iam from 'aws-cdk-lib/aws-iam'
import { StackProps } from "aws-cdk-lib";

interface CommonStackProps extends StackProps {
  environment: string
  repositoryRegex: string
  // Optional: aws-cdk-lib's OpenIdConnectProvider auto-fetches GitHub's current
  // OIDC thumbprint when this is omitted, so there's no hardcoded value to keep in sync.
  oidcThumbprint1?: string
  oidcThumbprint2?: string
}

export class GithubActionsStack extends cdk.Stack {
  public githubActionsRole: iam.Role

  constructor(scope: Construct, id: string, props: CommonStackProps) {
    super(scope, id, props)

    const githubOidcProvider = new iam.OpenIdConnectProvider(this, `GithubOidcProvider`, {
      url: 'https://token.actions.githubusercontent.com',
      thumbprints: (props.oidcThumbprint1 && props.oidcThumbprint2)
        ? [props.oidcThumbprint1, props.oidcThumbprint2]
        : undefined,
      clientIds: ['sts.amazonaws.com'],
    });

    this.githubActionsRole = new iam.Role(this, `SanakirjaGithubActionsUser`, {
      assumedBy: new iam.WebIdentityPrincipal(
        githubOidcProvider.openIdConnectProviderArn,
        {
          StringLike: {
            'token.actions.githubusercontent.com:sub': `repo:Opetushallitus/${props.repositoryRegex}:*`,
            'token.actions.githubusercontent.com:aud': 'sts.amazonaws.com',
          },
        },
      ),
      roleName: `github-actions-deployment-role-${props.environment}`,
    });

    const cdkPolicyStatement = new iam.PolicyStatement({
      actions: [ 'sts:AssumeRole', 'iam:PassRole'],
      resources: [
        "arn:aws:iam::*:role/cdk-hnb659fds-deploy-role-*",
        "arn:aws:iam::*:role/cdk-hnb659fds-file-publishing-*",
        "arn:aws:iam::*:role/cdk-hnb659fds-image-publishing-*",
        "arn:aws:iam::*:role/cdk-hnb659fds-lookup-*",
        "arn:aws:iam::*:role/cdk-hnb659fds-cfn-exec-*"
      ]
    })
    this.githubActionsRole.addToPolicy(cdkPolicyStatement)

  }
}
