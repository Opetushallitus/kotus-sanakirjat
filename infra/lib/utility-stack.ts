import * as iam from 'aws-cdk-lib/aws-iam';
import * as cdk from 'aws-cdk-lib';
import { Construct } from 'constructs';

interface UtilityStackProps extends cdk.StackProps {
    repositoryRegex: string
    // Optional: aws-cdk-lib's OpenIdConnectProvider auto-fetches GitHub's current
    // OIDC thumbprint when this is omitted, so there's no hardcoded value to keep in sync.
    oidcThumbprint1?: string
    oidcThumbprint2?: string
  }

export class UtilityStack extends cdk.Stack {
    public readonly githubActionsDeploymentRole: iam.Role;
    constructor(scope: Construct, id: string, props: UtilityStackProps) {
        super(scope, id, props);


        // Github Actions OIDC role
        const githubOidcProvider = new iam.OpenIdConnectProvider(this, `UtilityGithubOidcProvider`, {
            url: 'https://token.actions.githubusercontent.com',
            thumbprints: (props.oidcThumbprint1 && props.oidcThumbprint2)
                ? [props.oidcThumbprint1, props.oidcThumbprint2]
                : undefined,
            clientIds: ['sts.amazonaws.com'],
        });

        this.githubActionsDeploymentRole = new iam.Role(this, `UtilityGithubActionsUser`, {
            assumedBy: new iam.WebIdentityPrincipal(
                githubOidcProvider.openIdConnectProviderArn,
                {
                    StringLike: {
                        // GitHub's sub claim can be either `repo:ORG/REPO:...` or, with
                        // immutable org/repo IDs included, `repo:ORG@id/REPO@id:...` -
                        // match both rather than assuming one fixed format.
                        'token.actions.githubusercontent.com:sub': [
                            `repo:Opetushallitus/${props.repositoryRegex}:*`,
                            `repo:Opetushallitus@*/${props.repositoryRegex}@*:*`,
                        ],
                        'token.actions.githubusercontent.com:aud': 'sts.amazonaws.com',
                    },
                },
            ),
            roleName: 'utility-github-actions-deployment-role',
        });

    }
}
