import * as cdk from 'aws-cdk-lib';
import { Construct } from 'constructs';
import * as sns from 'aws-cdk-lib/aws-sns';
import * as chatbot from 'aws-cdk-lib/aws-chatbot';
import * as ssm from 'aws-cdk-lib/aws-ssm';
import * as logs from 'aws-cdk-lib/aws-logs';
import * as subscriptions from 'aws-cdk-lib/aws-sns-subscriptions'
import * as iam from "aws-cdk-lib/aws-iam";


export interface MonitorStackProps extends cdk.StackProps {
  slackChannelName: string,
  environment: string
}


export class MonitorStack extends cdk.Stack {
  public topic: cdk.aws_sns.Topic;
  public slackChannel: chatbot.SlackChannelConfiguration
  constructor(scope: Construct, id: string, props: MonitorStackProps) {
    super(scope, id, props);

    const slackChannelId = ssm.StringParameter.valueFromLookup(this, '/monitor/slack_channel_id');
    const slackWorkspaceId = ssm.StringParameter.valueFromLookup(this, '/monitor/slack_workspace_id');

    this.topic = new sns.Topic(this, `${props.environment}-cloudwatch-slack`, {
      topicName: `${props.environment}-cloudwatch-slack`,
    });

    this.topic.grantPublish(new iam.ServicePrincipal("cloudwatch.amazonaws.com"))

    this.slackChannel = new chatbot.SlackChannelConfiguration(this, 'SlackChannel', {
      slackChannelConfigurationName: `${props.slackChannelName}`,
      slackChannelId: slackChannelId,
      slackWorkspaceId: slackWorkspaceId,
      notificationTopics: [this.topic],
      loggingLevel: chatbot.LoggingLevel.INFO,
      logRetention: logs.RetentionDays.THREE_MONTHS,
    });

  }
}
