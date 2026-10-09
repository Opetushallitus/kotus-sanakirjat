import * as cdk from 'aws-cdk-lib/core';
import { StackProps } from 'aws-cdk-lib';
import { Construct } from 'constructs';
import { IVpc, SubnetType } from 'aws-cdk-lib/aws-ec2';
import { SubnetGroup } from 'aws-cdk-lib/aws-rds';

/*
    Resources that are shared between all Aurora Databases
*/

interface AuroraCommonStackProps extends StackProps {
    vpc: IVpc
}

export class AuroraCommonStack extends cdk.Stack {
  public readonly auroraSubnetGroup: SubnetGroup;



  constructor(scope: Construct, id: string, props: AuroraCommonStackProps) {
    super(scope, id, props);

    this.auroraSubnetGroup = new SubnetGroup(this, 'AuroraSubnetGroup', {
        description: 'Aurora Subnet Group',
        vpc: props.vpc,
        vpcSubnets: {
            onePerAz: false,
            subnetType: SubnetType.PRIVATE_ISOLATED,
          },
    })
  }
}