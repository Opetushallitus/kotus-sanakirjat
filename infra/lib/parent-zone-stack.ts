import * as cdk from 'aws-cdk-lib/core';
import { StackProps } from 'aws-cdk-lib';
import { Construct } from 'constructs';
import * as route53 from 'aws-cdk-lib/aws-route53';

interface Delegation {
  // Subdomain label only (e.g. 'dev'), not the full zone name.
  subdomain: string;
  // The sub-zone's own NS values - empty until that environment's HostedZoneStack
  // has actually been deployed (its NS values aren't known before that), in which
  // case no delegation record is created for it yet.
  nameServers: string[];
}

interface ParentZoneStackProps extends StackProps {
  domain: string;
  // Id of the already-existing sanakirja.testiopintopolku.fi zone (see
  // environments/dev.json's aws.parentZoneId).
  parentZoneId: string;
  delegations: Delegation[];
}

// sanakirja.testiopintopolku.fi already exists (created manually, in the dev
// account, before this stack existed) - this stack never creates, owns or
// modifies that zone itself. It only imports it by id/name and adds NS
// delegation record sets into it, down to each environment's own
// HostedZoneStack zone (dev./qa./prod.sanakirja.testiopintopolku.fi). No other
// records live here.
export class ParentZoneStack extends cdk.Stack {
  constructor(scope: Construct, id: string, props: ParentZoneStackProps) {
    super(scope, id, props);

    const parentZone = route53.HostedZone.fromHostedZoneAttributes(this, 'ParentHostedZone', {
      hostedZoneId: props.parentZoneId,
      zoneName: props.domain,
    });

    for (const { subdomain, nameServers } of props.delegations) {
      if (nameServers.length === 0) {
        continue;
      }
      new route53.NsRecord(this, `${subdomain}Delegation`, {
        zone: parentZone,
        recordName: subdomain,
        values: nameServers,
      });
    }
  }
}
