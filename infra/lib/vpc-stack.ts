import * as cdk from 'aws-cdk-lib';
import { Construct } from 'constructs';
import * as ec2 from 'aws-cdk-lib/aws-ec2';

interface VpcStackProps extends cdk.StackProps {
  vpc_cidr: string
  availability_zones: number
}

// Each environment gets a /23 (8x /26 blocks, per OPH admin's networking design).
// 3 subnet tiers x 2 AZs = 6 /26 subnets, leaving 2 blocks of headroom in the /23
// for future use. Don't raise availability_zones above 2 without also shrinking
// the number of tiers - 3 tiers x 3 AZs would need 9 /26 blocks, more than the /23 has.
export class VpcStack extends cdk.Stack {
  readonly vpc: ec2.IVpc;
  constructor(scope: Construct, id: string, props: VpcStackProps) {
    super(scope, id, props);


  this.vpc = new ec2.Vpc(this, 'VPC', {
    ipAddresses: ec2.IpAddresses.cidr(props.vpc_cidr),
    natGateways: props.availability_zones,
    maxAzs: props.availability_zones,
    subnetConfiguration: [
      {
        cidrMask: 26,
        name: 'public',
        subnetType: ec2.SubnetType.PUBLIC,
      },
      {
        cidrMask: 26,
        name: 'application',
        subnetType: ec2.SubnetType.PRIVATE_WITH_EGRESS,
      },
      {
        cidrMask: 26,
        name: 'rds',
        subnetType: ec2.SubnetType.PRIVATE_ISOLATED,
      },
    ],
  });

   }
}
