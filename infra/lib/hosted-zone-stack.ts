import * as cdk from 'aws-cdk-lib/core';
import { StackProps } from 'aws-cdk-lib';
import { Construct } from 'constructs';
import * as route53 from 'aws-cdk-lib/aws-route53';
import { IVpc } from 'aws-cdk-lib/aws-ec2';


interface HostedZoneStackProps extends StackProps {
    domain: string,
    vpc: IVpc,
}

export class HostedZoneStack extends cdk.Stack {
  public readonly publicHostedZone: route53.HostedZone;
  public readonly privateHostedZone: route53.HostedZone;


  constructor(scope: Construct, id: string, props: HostedZoneStackProps) {
    super(scope, id, props);

    this.publicHostedZone = new route53.PublicHostedZone(this, 'PublicHostedZone', {
      zoneName: props.domain
    });

    this.privateHostedZone = new route53.PrivateHostedZone(this, 'PrivateHostedZone', {
      zoneName: props.domain,
      vpc: props.vpc
    });
    
  }
}