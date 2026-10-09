import { CfnOutput, Duration, RemovalPolicy, Stack, StackProps } from "aws-cdk-lib";
import * as efs from 'aws-cdk-lib/aws-efs';
import * as backup from 'aws-cdk-lib/aws-backup';
import * as events from 'aws-cdk-lib/aws-events';
import { Construct } from "constructs";
import { ISecurityGroup, IVpc } from "aws-cdk-lib/aws-ec2";
import { AccessPoint, ThroughputMode } from "aws-cdk-lib/aws-efs";
import { IKey } from "aws-cdk-lib/aws-kms";
import * as iam from "aws-cdk-lib/aws-iam"

interface EfsStackProps extends StackProps {
    securityGroup: ISecurityGroup;
    vpc: IVpc
    accessPointPath: string,
    throughputMode: ThroughputMode
    kmsKey: IKey
    environment: string
}

// Persistent storage for eXist-db's data directory. Fargate tasks can't mount EBS,
// so eXist-db's container mounts this EFS file system instead (see ecs-service.ts `efs` prop).
// The dictionary content here doesn't change day-to-day, so no continuous backup is
// needed - a daily AWS Backup snapshot with a 7-day retention (matching the RDS policy)
// is enough to repair from in case of trouble.
export class EfsStack extends Stack {
    public readonly fileSystem: efs.FileSystem;
    public readonly fileSystemId: string;
    public readonly accessPoint: AccessPoint;
    public readonly efsPolicyStatement: iam.PolicyStatement;

    constructor(scope: Construct, id: string, props: EfsStackProps) {
        super(scope, id, props);

        this.fileSystem = new efs.FileSystem(this, 'ExistDbEfs', {
            vpc: props.vpc,
            lifecyclePolicy: efs.LifecyclePolicy.AFTER_30_DAYS,
            performanceMode: efs.PerformanceMode.GENERAL_PURPOSE,
            throughputMode: props.throughputMode,
            securityGroup: props.securityGroup,
            encrypted: true,
            kmsKey: props.kmsKey,
        });

        this.accessPoint = new AccessPoint(this, 'AccessPoint', {
            fileSystem: this.fileSystem,
            path: props.accessPointPath,
            createAcl: {
                ownerGid: "1000",
                ownerUid: "1000",
                permissions: "755"
            },
            posixUser: {
                uid: "1000",
                gid: "1000"
            }
        });

        this.fileSystemId = this.fileSystem.fileSystemId

        this.efsPolicyStatement = new iam.PolicyStatement({
            actions: [
              'elasticfilesystem:DescribeFileSystems',
              'elasticfilesystem:ClientWrite',
              'elasticfilesystem:ClientMount',
              'elasticfilesystem:DescribeMountTargets'
            ],
            resources: [this.fileSystem.fileSystemArn]
          })

        const backupVault = new backup.BackupVault(this, 'ExistDbBackupVault', {
            backupVaultName: `${props.environment}-existdb-efs-backup`,
            encryptionKey: props.kmsKey,
            removalPolicy: RemovalPolicy.DESTROY,
        });

        const backupPlan = new backup.BackupPlan(this, 'ExistDbBackupPlan', {
            backupPlanName: `${props.environment}-existdb-efs-backup-plan`,
            backupVault,
            backupPlanRules: [
                new backup.BackupPlanRule({
                    ruleName: 'Daily7DayRetention',
                    scheduleExpression: events.Schedule.cron({ hour: '5', minute: '0' }),
                    deleteAfter: Duration.days(7),
                }),
            ],
        });

        backupPlan.addSelection('ExistDbBackupSelection', {
            resources: [
                backup.BackupResource.fromEfsFileSystem(this.fileSystem),
            ],
        });

        new CfnOutput(this, 'FileSystemId', {
            value: this.fileSystem.fileSystemId,
        });
    }
}
