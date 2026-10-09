import * as cdk from 'aws-cdk-lib';
import * as ec2 from 'aws-cdk-lib/aws-ec2';
import * as servicediscovery from 'aws-cdk-lib/aws-servicediscovery';
import { StackProps } from "aws-cdk-lib";

interface NamespaceStackProps extends StackProps {
    environment: string
    projectName: string
}

export class NamespaceStack extends cdk.Stack {
    public readonly privateDnsNamespace: servicediscovery.PrivateDnsNamespace;

    constructor(scope: cdk.App, id: string, vpc: ec2.IVpc, props: NamespaceStackProps) {
        super(scope, id, props);

        this.privateDnsNamespace = new servicediscovery.PrivateDnsNamespace(this, 'Namespace', {
            name: `${props.environment}.${props.projectName}.local`,
            vpc,
            description: 'Shared service discovery namespace',
        });
    }
}