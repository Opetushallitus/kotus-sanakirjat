// create CDK stack that creates a Linux bastion host
import * as ec2 from "aws-cdk-lib/aws-ec2";
import { IKey } from "aws-cdk-lib/aws-kms";
import { Construct } from "constructs";
import { Stack, StackProps } from "aws-cdk-lib";
import * as iam from "aws-cdk-lib/aws-iam";
import * as s3 from "aws-cdk-lib/aws-s3";
import {readFileSync} from 'fs';

interface BastionStackProps extends StackProps {
    vpc: ec2.IVpc;
    kmsKey: IKey
    securityGroup: ec2.ISecurityGroup
    environment: string
    transferBucket: s3.IBucket
    existDbHost: string
}

// TODO: once the Packer bastion-AMI pipeline exists, pass its output AMI here via
// `machineImage: ec2.MachineImage.lookup(...)` / an SSM parameter instead of relying
// on ec2.BastionHostLinux's default Amazon Linux image.
export class BastionStack extends Stack {
    constructor(scope: Construct, id: string, props: BastionStackProps) {
      super(scope, id, props);

      const allowSessionManager = new iam.PolicyStatement({
        actions: [
          'ssm:UpdateInstanceInformation',
          'ssmmessages:CreateControlChannel',
          'ssmmessages:CreateDataChannel',
          'ssmmessages:OpenControlChannel',
          'ssmmessages:OpenDataChannel',
          'ec2messages:GetMessages',
          'ssm:ListInstanceAssociations',
          'ssm:ListAssociations',
          'ssm:GetDeployablePatchSnapshotForInstance',
          'ssm:GetDocument',
          'ssm:UpdateInstanceAssociationStatus',
          'ssm:PutInventory',
          'elasticfilesystem:DescribeFileSystems',
          'elasticfilesystem:ClientWrite',
          'elasticfilesystem:ClientMount',
          'elasticfilesystem:DescribeMountTargets',
        ],
        resources: ['*']
      })

      const allowKmsDecrypt = (keyArn: string) => {
        return new iam.PolicyStatement({
          actions: [
            'kms:Decrypt'
          ],
          resources: [keyArn]
        })
      }

      // Deliberately strict: Session Manager only (no inbound SSH rule from the
      // Internet). Add a restricted-IP SSH ingress rule to `securityGroup` once
      // the OPH admins provide the allowed IP list - keep the bastion SG as
      // strict as possible otherwise.
      const bastionHost = new ec2.BastionHostLinux(this, 'BastionHost', {
        vpc: props.vpc,
        instanceName: `${props.environment}-bastion-host`,
        securityGroup: props.securityGroup,
        instanceType: ec2.InstanceType.of(ec2.InstanceClass.T3, ec2.InstanceSize.NANO),
        blockDevices: [{
          deviceName: '/dev/sdh',
          volume: ec2.BlockDeviceVolume.ebs(10, {
            encrypted: true,
            kmsKey: props.kmsKey
          }),
        }],
        subnetSelection: { subnetType: ec2.SubnetType.PRIVATE_WITH_EGRESS },
        // Without this, CloudFormation applies a UserData change via an in-place
        // stop/start of the same instance - and cloud-init only ever runs
        // scripts-user once per instance id, so the new script would silently
        // never execute. Force a real replacement so cloud-init runs fresh.
        userDataCausesReplacement: true
      });

      const userDataScript = readFileSync('./scripts/bastion_userdata.sh', 'utf8');

      // Consumed by bastion_userdata.sh to point the eXist-db client at this
      // environment's service instead of a hardcoded host.
      bastionHost.instance.addUserData(`export EXISTDB_HOST=${props.existDbHost}`);
      bastionHost.instance.addUserData(userDataScript);
      bastionHost.role.addToPrincipalPolicy(allowSessionManager)
      bastionHost.role.addToPrincipalPolicy(allowKmsDecrypt(props.kmsKey.keyArn))

      // Read-only pull access to the database transfer bucket - grantRead also
      // covers kms:Decrypt on the bucket's own encryption key automatically.
      props.transferBucket.grantRead(bastionHost.role)
}}
