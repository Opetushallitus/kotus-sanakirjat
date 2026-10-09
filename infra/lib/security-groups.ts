import * as cdk from 'aws-cdk-lib/core';
import * as ec2 from 'aws-cdk-lib/aws-ec2';
import { StackProps } from 'aws-cdk-lib';
import { Construct } from 'constructs';

/*
Security Groups for resources are defined here.
Security Groups defined in a centralized manner like this won't generate
an "order of creation" - dependency between the services / databases,
but adds a little operational complexity (when creating a new
service / database, Security Groups and SG rules must first be defined here)
*/

interface SecurityGroupStackProps extends StackProps {
  vpc: ec2.IVpc
}

export class SecurityGroupStack extends cdk.Stack {
  public readonly albSecurityGroup: ec2.SecurityGroup;
  public readonly bastionSecurityGroup: ec2.SecurityGroup;
  public readonly webServiceSecurityGroup: ec2.SecurityGroup;
  public readonly existDbServiceSecurityGroup: ec2.SecurityGroup;
  public readonly searchIndexAuroraSecurityGroup: ec2.SecurityGroup;
  public readonly efsSecurityGroup: ec2.SecurityGroup;

  constructor(scope: Construct, id: string, props: SecurityGroupStackProps) {
    super(scope, id, props);

    // Security Groups
    this.albSecurityGroup = new ec2.SecurityGroup(this, 'AlbSecurityGroup', {
      vpc: props.vpc,
      allowAllOutbound: true,
    });

    // Bastion SG stays deliberately minimal - it's the only host reachable
    // from outside the VPC other than the ALB, so keep it as strict as possible.
    // No inbound rules are added here beyond what Session Manager needs (none -
    // SSM uses outbound-only connections). Add a restricted-IP SSH ingress rule
    // once the OPH admins provide the allowed IP list; do not open it to 0.0.0.0/0.
    this.bastionSecurityGroup = new ec2.SecurityGroup(this, 'BastionSecurityGroup', {
      vpc: props.vpc,
      allowAllOutbound: true,
    });

    this.webServiceSecurityGroup = new ec2.SecurityGroup(this, 'WebServiceSecurityGroup', {
      vpc: props.vpc,
      allowAllOutbound: true,
    });

    this.existDbServiceSecurityGroup = new ec2.SecurityGroup(this, 'ExistDbServiceSecurityGroup', {
      vpc: props.vpc,
      allowAllOutbound: true,
    });

    this.searchIndexAuroraSecurityGroup = new ec2.SecurityGroup(this, 'SearchIndexAuroraSecurityGroup', {
      vpc: props.vpc,
      allowAllOutbound: true,
    });

    this.efsSecurityGroup = new ec2.SecurityGroup(this, 'EfsSecurityGroup', {
      vpc: props.vpc,
      allowAllOutbound: true,
    });

    // allow port 443 to the ALB from the Internet
    this.albSecurityGroup.addIngressRule(
      ec2.Peer.anyIpv4(),
      ec2.Port.tcp(443)
    );

    // ALB -> web tier (Apache/nginx + php-fpm)
    this.webServiceSecurityGroup.addIngressRule(
      this.albSecurityGroup,
      ec2.Port.tcp(8080)
    );
    this.webServiceSecurityGroup.addIngressRule(
      this.bastionSecurityGroup,
      ec2.Port.tcp(8080)
    );

    // web tier -> eXist-db REST/XQuery API (Jetty's real port, per the legacy
    // install's exist6-db.service - not 8080, that's the web tier's port)
    this.existDbServiceSecurityGroup.addIngressRule(
      this.webServiceSecurityGroup,
      ec2.Port.tcp(8086)
    );
    this.existDbServiceSecurityGroup.addIngressRule(
      this.bastionSecurityGroup,
      ec2.Port.tcp(8086)
    );

    // web tier -> search-word Postgres (Aurora Serverless v2)
    this.searchIndexAuroraSecurityGroup.addIngressRule(
      this.webServiceSecurityGroup,
      ec2.Port.tcp(5432)
    );
    this.searchIndexAuroraSecurityGroup.addIngressRule(
      this.bastionSecurityGroup,
      ec2.Port.tcp(5432)
    );

    // eXist-db -> its EFS-backed data directory
    this.efsSecurityGroup.addIngressRule(
      this.existDbServiceSecurityGroup,
      ec2.Port.tcp(2049)
    );
    this.efsSecurityGroup.addIngressRule(
      this.bastionSecurityGroup,
      ec2.Port.tcp(2049)
    );
  }

}
