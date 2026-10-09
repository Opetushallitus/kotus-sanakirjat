// S3 bucket for manually staging database content (search-word CSV export,
// eXist-db article XML) so it can be pulled onto the bastion host and loaded
// from there - avoids opening any file-transfer channel to the bastion itself.
import * as cdk from 'aws-cdk-lib'
import * as s3 from 'aws-cdk-lib/aws-s3'
import { IKey } from 'aws-cdk-lib/aws-kms'
import { Construct } from 'constructs'
import { Stack, StackProps, RemovalPolicy, Duration } from 'aws-cdk-lib'

interface TransferBucketStackProps extends StackProps {
    environment: string
    kmsKey: IKey
}

export class TransferBucketStack extends Stack {
    readonly bucket: s3.Bucket

    constructor(scope: Construct, id: string, props: TransferBucketStackProps) {
        super(scope, id, props)

        this.bucket = new s3.Bucket(this, 'TransferBucket', {
            // S3 bucket names are globally unique across all AWS accounts, not just
            // this one - alb-stack.ts's logBucket hit exactly this collision once
            // already. Account id makes this unique.
            bucketName: `kotus-sanakirja-database-transfer-${props.environment}-${cdk.Stack.of(this).account}`,
            accessControl: s3.BucketAccessControl.PRIVATE,
            blockPublicAccess: s3.BlockPublicAccess.BLOCK_ALL,
            objectOwnership: s3.ObjectOwnership.BUCKET_OWNER_PREFERRED,
            versioned: true,
            encryption: s3.BucketEncryption.KMS,
            encryptionKey: props.kmsKey,
            enforceSSL: true,
            lifecycleRules: [
                {
                    id: 'ExpireOldVersions',
                    noncurrentVersionExpiration: Duration.days(30)
                }
            ],
            // Transient staging data only (re-uploaded from source files each time) -
            // fine to destroy along with the stack, unlike a durable backup bucket.
            removalPolicy: RemovalPolicy.DESTROY,
            autoDeleteObjects: true
        })
    }
}
