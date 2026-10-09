import { Repository, TagMutability } from "aws-cdk-lib/aws-ecr";
import { Stack, StackProps, RemovalPolicy } from "aws-cdk-lib";
import * as iam from "aws-cdk-lib/aws-iam";
import { Construct } from "constructs";

interface EcrStackProps extends StackProps {
    serviceName: string;
    githubActionsDeploymentRole: iam.Role
    orgId: string
    projectAwsOrgOrganizationalUnit: string
}

export class EcrStack extends Stack {
    public readonly repository: Repository;

    constructor(scope: Construct, id: string, props: EcrStackProps) {
        super(scope, id, props);

        this.repository = new Repository(this, "Repository", {
            repositoryName: `${props.serviceName}`,
            removalPolicy: RemovalPolicy.DESTROY,
            imageTagMutability: TagMutability.IMMUTABLE
        });

        this.repository.addToResourcePolicy(
            new iam.PolicyStatement({
              principals: [new iam.AnyPrincipal()],
              actions: [
                "ecr:BatchCheckLayerAvailability",
                "ecr:BatchGetImage",
                "ecr:DescribeImages",
                "ecr:DescribeRepositories",
                "ecr:GetDownloadUrlForLayer"
              ],
              conditions: {
                "ForAnyValue:StringLike": {
                  "aws:PrincipalOrgPaths": [`${props.orgId}/*/${props.projectAwsOrgOrganizationalUnit}/*`],
                },
              },
            })
          );
        this.repository.grantPush(props.githubActionsDeploymentRole); 
    }
}