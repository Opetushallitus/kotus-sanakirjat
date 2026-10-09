<?php

/**
 * inc/QueryXML.php
 *
 * Class for all communication to eXist-db.
 * Uses REST APIs
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author:		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013, original Soap/WSDL-version
 *				Outi Lehtinen, 2020, REST API -toteutus
*/

final class QueryXML {

	// Store session id for queries.
	protected $_sessionId;

	// Class constructor, to be executed when invoking a new instance.
	public function __construct() {

		// Make the variable usable inside this function scope.
		global $cfg;

		try {
			
			// Voisi testata, vastaako eXist?


		} catch (SoapFault $e) {

			throw new QueryXMLException($e);

		}

		return $this;

	}

	// Artikkelin haku RESTXQ-rajapinnan kautta
	// CURL/POST, palautus XML/HTML
	// Pakotettu http-yhteyden sulkeminen / 2.3.2022 OL
	public static function queryRESTXQarticle($query = false, $view = 'full')
	{
		global $cfg;
		$resturl = $cfg['XML']['RESTXQ_ARTICLE'] . '/' . $view;
		$curl = curl_init($resturl);
		$headers = array('Accept: text/xml', 'Content-Type: text/xml', 'Connection: close');

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$result = curl_exec($curl);

		curl_close($curl);

		return $result;
	}

	// Artikkelin haku ja uudenlainen osumien korostus RESTXQ-rajapinnan kautta
	// Pakotettu http-yhteyden sulkeminen / 2.3.2022 OL
	public static function queryRESTXQarticleMatches($articleid, $query = false, $view = 'full')
	{
		global $cfg;
		$resturl = $cfg['XML']['RESTXQ_ARTICLE_MATCH'] . '/' . urlencode($articleid) . '/' . $view;
		$curl = curl_init($resturl);
		$headers = array('Accept: text/xml', 'Content-Type: text/xml', 'Connection: close');

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$result = curl_exec($curl);

		curl_close($curl);

		return $result;
	}

	// Artikkelin haku ja KWIC-osuman korostus
	public static function queryRESTXQarticleKWIC($query = false, $view = 'full', $context = 'DictionaryEntry', $lemma = 'foobarkooma')
	{
		global $cfg;
		$resturl = $cfg['XML']['RESTXQ_ARTICLE_KWIC_MATCH'] . '/' . $view . '/' . $context . '/' . $lemma;

		$curl = curl_init($resturl);
		$headers = array('Accept: text/xml', 'Content-Type: text/xml', 'Connection: close');

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$result = curl_exec($curl);

		curl_close($curl);

		return $result;
	}

	// Tarkennettu haku REST API -rajapinnan kautta / 28.1.2020 OL
	// CURL/POST, palauttaa XML+JSON
	// Pakotettu http-yhteyden sulkeminen / 2.3.2022 OL
	public static function queryRESTAPI($query)
	{
		global $cfg;
		$resturl = $cfg['XML']['REST'];
		$curl = curl_init($resturl);
		$headers = array('Accept: application/json', 'Content-Type: text/xml', 'Connection: close');

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$result = curl_exec($curl);

		curl_close($curl);

		return $result;
	}

}

?>
