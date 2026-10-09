// Create a new cdk stack with a public ALB and a security group for it. Add a https listener with http redirect rule to https. Create outputs for alb and listener.
import * as cdk from 'aws-cdk-lib';
import * as elbv2 from 'aws-cdk-lib/aws-elasticloadbalancingv2';
import * as ec2 from 'aws-cdk-lib/aws-ec2';
import * as route53 from 'aws-cdk-lib/aws-route53';
import * as targets from 'aws-cdk-lib/aws-route53-targets';
import * as acm from 'aws-cdk-lib/aws-certificatemanager';
import * as s3 from 'aws-cdk-lib/aws-s3';
import { Construct } from 'constructs';
import { aws_cloudwatch_actions } from 'aws-cdk-lib';
import {Alarm,ComparisonOperator,TreatMissingData} from "aws-cdk-lib/aws-cloudwatch";
import { Topic } from 'aws-cdk-lib/aws-sns';

interface AlbStackProps extends cdk.StackProps {
    vpc: ec2.IVpc,
    securityGroupId: string,
    domain: string
    publicHostedZone: route53.IHostedZone
    environment: 'dev' | 'qa' | 'prod'
    alarmSnsTopic: Topic
}

export class AlbStack extends cdk.Stack {
    readonly alb: elbv2.ApplicationLoadBalancer;
    readonly albListener: elbv2.ApplicationListener;
    readonly certificate: acm.ICertificate;
    constructor(scope: Construct, id: string, props: AlbStackProps) {
        super(scope, id, props);

        // New internet-facing application load balancer, import vpc from the VpcStack
        this.alb = new elbv2.ApplicationLoadBalancer(this, 'alb', {
            vpc: props.vpc,
            vpcSubnets: {
                onePerAz: true,
                subnetType: ec2.SubnetType.PUBLIC
            },
            internetFacing: true,
            http2Enabled: true,
            securityGroup: ec2.SecurityGroup.fromSecurityGroupId(
                this,
                "ImmutableSecurityGroup",
                props.securityGroupId,
                { mutable: false }
            )
        });

        const logBucket = new s3.Bucket(this, `albLogBucket`, {
          // S3 bucket names are globally unique across all AWS accounts, not just
          // this one - a plain `alb-logs-${environment}` collided with a bucket
          // some other AWS customer already owns. Account id makes this unique.
          bucketName: `sanakirja-alb-logs-${props.environment}-${cdk.Stack.of(this).account}`,
          accessControl: s3.BucketAccessControl.PRIVATE,
          blockPublicAccess: s3.BlockPublicAccess.BLOCK_ALL,
          objectOwnership: s3.ObjectOwnership.BUCKET_OWNER_PREFERRED,
          lifecycleRules: [
            {
              id: 'ExpireOldVersions',
              noncurrentVersionExpiration: cdk.Duration.days(30) // Retain old versions for 30 days
            }
          ]
        })
    
        this.alb.logAccessLogs(logBucket)
        // Use this when an actual domain is available for ACM certs
        //    new alb listener
        this.certificate = new acm.Certificate(this, 'Certificate', {
            domainName: `${props.domain}`,
            validation: acm.CertificateValidation.fromDns(props.publicHostedZone),
            subjectAlternativeNames: [`alb.${props.domain}`],
        });


        // route53 alias record for cloudfront
        new route53.ARecord(this, 'AliasRecord', {
            zone: props.publicHostedZone,
            recordName: `alb.${props.domain}`,
            target: route53.RecordTarget.fromAlias(new targets.LoadBalancerTarget(this.alb)),
        });

        this.albListener = this.alb.addListener('alb-listener', {
            port: 443,
            protocol: elbv2.ApplicationProtocol.HTTPS,
            open: false,
            certificates: [this.certificate],
            sslPolicy: elbv2.SslPolicy.TLS12,
        });


        // create ALB default target group
        const albDefaultTargetGroup = new elbv2.ApplicationTargetGroup(this, 'alb-target-group', {
            vpc: props.vpc,
            port: 80,
            protocol: elbv2.ApplicationProtocol.HTTP,
            targetType: elbv2.TargetType.IP,
            healthCheck: {
                healthyThresholdCount: 5,
                interval: cdk.Duration.seconds(30),
                path: '/',
                protocol: elbv2.Protocol.HTTP,
                timeout: cdk.Duration.seconds(10),
                unhealthyThresholdCount: 2
            },
        });

        this.albListener.addTargetGroups('dummyTargetGroup', {
            targetGroups: [albDefaultTargetGroup]
        });

        const alarmSnsAction = new aws_cloudwatch_actions.SnsAction(props.alarmSnsTopic)
        

        const alb5xxAlarm = new Alarm(this, `Alb5xxAlarm`, {
              alarmName: 'Alb5xxAlarm',
              metric: this.alb.metrics.httpCodeElb(elbv2.HttpCodeElb.ELB_5XX_COUNT, {
                statistic: 'Sum',
                period: cdk.Duration.minutes(5)
              }),
              threshold: 10,
              evaluationPeriods: 1,
              comparisonOperator: ComparisonOperator.GREATER_THAN_OR_EQUAL_TO_THRESHOLD,
              treatMissingData: TreatMissingData.NOT_BREACHING
            })
            alb5xxAlarm.addAlarmAction(alarmSnsAction)
            alb5xxAlarm.addOkAction(alarmSnsAction)
    }
}
