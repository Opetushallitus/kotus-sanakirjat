<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * cfg/QuerySQL.php
 * 
 * Configuration settings for PDO (PHP Database Object).
 * FO uses an extended subclass (QuerySQL) to handle MySQL connections.
 * 
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
*/

// Deliberate deviation from the delivered source (everything else in this zip
// is kept byte-identical) - the infra only provisions PostgreSQL (Aurora), not
// MySQL, so this points at the real Aurora cluster instead of the hardcoded
// localhost/MySQL values the app shipped with. See inc/QuerySQL.php for the
// matching PDO driver change (mysql: -> pgsql:).
// POSTGRESQL_HOST/POSTGRESQL_PORT/PG_PASS are injected by the ECS task
// definition (bin/infra.ts's WebEcsService) - the getenv() fallbacks are only
// for running this container standalone, outside ECS.
// 'db_admin' matches the Aurora cluster's actual master username
// (infra/lib/aurora-serverless-database.ts). 'postgres' is the only database
// that actually exists on the cluster so far - no app schema/data has been
// loaded into it yet (that's a separate, not-yet-built step: the CSV search-
// word load from the editorial content pipeline), so queries against
// app-specific tables (hakusanat, kohde) will still fail until that happens.
$cfg['SQL'] = array(

	// Aurora Postgres cluster endpoint.
	'HOSTNAME'	=> getenv('POSTGRESQL_HOST') ?: "localhost",

	// Aurora Postgres cluster port.
	'PORTNUM'	=> (int) (getenv('POSTGRESQL_PORT') ?: 5432),

	// Username (read-only user).
	'USERNAME'	=> "ksuser",

	// Password (Aurora master password, from Secrets Manager via PG_PASS).
	'PASSWORD'	=> getenv('PG_PASS') ?: "",

	// Database to be used
	'DATABASE'	=> "ks"

);

?>
