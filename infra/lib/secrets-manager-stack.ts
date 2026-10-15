import * as cdk from "aws-cdk-lib";
import { Construct } from "constructs";
import * as secretsmanager from "aws-cdk-lib/aws-secretsmanager";
import { IKey } from "aws-cdk-lib/aws-kms";

interface SecretManagerStackProps extends cdk.StackProps {
    kmsKey: IKey;
}

export type SecretEntry = {
    secretKey: string;
    envVarName: string;
    path: string;
};

export type Secrets = {
    [key: string]: SecretEntry;
};

// Postgres role the web app connects as. SELECT-only - the role, its grants
// and its '/auroradbs/search-index/readonly-user-password' secret are all
// managed by hand per environment, not by CDK (the secret is only referenced
// by name, see PG_PASS below).
export const SEARCH_INDEX_READONLY_USERNAME = 'ksuser';

// Stack for secrets generated on the fly (mostly resources that don't support Parameter Store)
export class SecretManagerStack extends cdk.Stack {
    public readonly searchIndexAuroraPassword: secretsmanager.Secret;
    public readonly existDbAdminPassword: secretsmanager.Secret;

    public readonly secrets: Secrets = {
        // The web app only reads from the search index, so it connects as the
        // read-only role, not the Aurora master user. The secret must already
        // exist in the target account before WebEcsService is deployed, or its
        // tasks fail to launch.
        PG_PASS: { envVarName: 'PG_PASS', path: '/auroradbs/search-index/readonly-user-password', secretKey: 'password' },
        EXISTDB_ADMIN_PASS: { envVarName: 'EXISTDB_ADMIN_PASS', path: '/service/existdb/ADMIN_PASS', secretKey: 'secretkey' },
    }

    constructor(scope: Construct, id: string, props: SecretManagerStackProps) {
        super(scope, id, props);

        this.searchIndexAuroraPassword = new secretsmanager.Secret(this, 'SearchIndexAuroraPassword', {
            secretName: '/auroradbs/search-index/master-user-password',
            generateSecretString: {
                secretStringTemplate: JSON.stringify({ username: "db_admin" }),
                generateStringKey: 'password',
                passwordLength: 24,
                excludeCharacters: '@%*()_+=`~{}|[]\\:";\'?,./'
            },
        });

        this.existDbAdminPassword = new secretsmanager.Secret(this, 'ExistDbAdminPassword', {
            secretName: '/service/existdb/ADMIN_PASS',
            generateSecretString: {
                secretStringTemplate: JSON.stringify({}),
                generateStringKey: 'secretkey',
                passwordLength: 24,
                // Also excludes <>& (on top of the shell-unsafe punctuation
                // below) - this value gets embedded unescaped into an XQuery
                // string literal by docker-entrypoint.sh's first-boot
                // sm:passwd() call, and an unescaped '&' there breaks the
                // XQuery parser (confirmed: caused every existdb task to
                // crash-loop until this was caught and the live secret's
                // value was manually rotated to one that doesn't contain it).
                excludeCharacters: '@%*()_+=`~{}|[]\\:";\'?,./<>&'
            },
        });
    }
}
