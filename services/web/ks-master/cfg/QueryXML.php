<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * cfg/QueryXML.php
 * 
 * Configuration settings for XML and eXist.
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * 				Outi Lehtinen, 2020
*/

// Deliberate deviation from the delivered source (same treatment as
// cfg/QuerySQL.php): eXist-db runs as its own ECS task, reached over Cloud
// Map service discovery, not on the same host as the web tier - so hardcoded
// "localhost" never resolves to it. EXISTDB_HOST/EXISTDB_PORT are injected by
// the ECS task definition (bin/infra.ts's WebEcsService); the getenv()
// fallbacks are only for running this container standalone, outside ECS.
$existBase = "http://" . (getenv('EXISTDB_HOST') ?: "localhost") . ":" . (getenv('EXISTDB_PORT') ?: "8086");

// Settings for eXist
$cfg['XML'] = array(

	// Location of the eXist WSDL query service.
	//'WSDL'	=> "$existBase/exist/services/Query?wsdl",
	// Rest service URL: tarkennettu haku
	'REST'	=> "$existBase/exist/rest/",
	// RestQX service URL: artikkelin haku
	'RESTXQ_ARTICLE' => "$existBase/exist/restxq/articlexml/ks",
	// RestQX service URL: artikkelin haku, tarkennetun haun tulokset korostettuna
	'RESTXQ_ARTICLE_MATCH' => "$existBase/exist/restxq/articlematches/ks",
	// RestQX service URL: artikkelin haku, KWIC-haun tulos korostettuna
	//'RESTXQ_ARTICLE_KWIC_MATCH' => "$existBase/exist/restxq/articlekwicmatch/ks",
	
	// Username
	'USERNAME'	=> "guest",
	
	// Password
	'PASSWORD'	=> "guest",

    // Database to be used
    'DATABASE'	=> "/db/ks",
	
    // Stylesheets to be used
    'STYLESHEETS'	=> "/db/xsl/ks"
	
);

?>
