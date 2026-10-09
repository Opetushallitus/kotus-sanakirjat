// Create new CDK stack for KMS keys
import { Stack, StackProps, RemovalPolicy } from 'aws-cdk-lib';
import { Key } from 'aws-cdk-lib/aws-kms';
import { Construct } from 'constructs';
import * as aws_iam from 'aws-cdk-lib/aws-iam';

interface KmsStackProps extends StackProps {
    environment: string,
}

export class KmsStack extends Stack {
    readonly rdsKmsKey: Key;
    readonly efsKmsKey: Key;
    readonly cloudwatchLogsKmsKey: Key;
    readonly s3KmsKey: Key;
    readonly ebsKmsKey: Key;
    readonly parameterStoreKey: Key;
    readonly secretsManagerKey: Key;

    constructor(scope: Construct, id: string, props: KmsStackProps) {
        super(scope, id, props);

        // Change removal policies to something sensible when stacks are more mature
        this.rdsKmsKey = new Key(this, 'rdsKmsKey', {
            alias: `alias/${props.environment}-rds-key`,
            removalPolicy: RemovalPolicy.DESTROY,
        });

        this.efsKmsKey = new Key(this, 'efsKmsKey', {
            alias: `alias/${props.environment}-efs-key`,
            removalPolicy: RemovalPolicy.DESTROY,
        });

        this.s3KmsKey = new Key(this, 's3KmsKey', {
            alias: `alias/${props.environment}-s3-key`,
            removalPolicy: RemovalPolicy.DESTROY,
        });

        this.cloudwatchLogsKmsKey = new Key(this, 'cloudwatchLogsKmsKey', {
            alias: `alias/${props.environment}-cloudwatch-logs-key`,
            removalPolicy: RemovalPolicy.DESTROY,
        });

        this.cloudwatchLogsKmsKey.grantEncryptDecrypt(
            new aws_iam.ServicePrincipal(`logs.${this.region}.amazonaws.com`)
        );

        this.ebsKmsKey = new Key(this, 'ebsKmsKey', {
            alias: `alias/${props.environment}-ebs-key`,
            removalPolicy: RemovalPolicy.DESTROY,
        });

        this.parameterStoreKey = new Key(this, 'parameterStoreKmsKey', {
            alias: `alias/${props.environment}-parameter-store-key`,
            removalPolicy: RemovalPolicy.DESTROY,
        });

        this.secretsManagerKey = new Key(this, 'secretsManagerKmsKey', {
            alias: `alias/${props.environment}-secrets-manager-key`,
            removalPolicy: RemovalPolicy.DESTROY,
        });
    }
}
