<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * app/models/SearchModel.php
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * 				Outi Lehtinen ja Jari Vihtari, 2021
*/

class SearchModel extends Model {

	/*
		HAKUSANALUETTELO
	*/

	// Uusi funktio / 16.7.2019 OL
	// Hakee tietokannasta id:n perusteella +-100 hakusanaa
	public function getHeadwordListById($id = 1) {
		
		try {
			// Get SQL (MySQL) handle.
			$sql = $this->getSQLHandle();
				
			// Prepare query
			$query = $sql->prepare("SELECT * FROM hakusanat WHERE id > ? - 100 AND id < ? + 100 ORDER BY id");
			$query->bindValue(1, $id);
			$query->bindValue(2, $id);
				
			// Set a fetch mode and execute the query.
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$query->execute();
			$result = $query->fetchAll();

			return $result;
			
		} catch (QuerySQLException $e) {
		
		} catch (DICTException $e) {
		
		}
			
	}
	// Uusi funktio / 16.7.2019 OL
	// Hakee tietokannasta alkukirjaimen perusteella 100 hakusanaa (lisätty LOWER() / 5.6.2026 jv)
	public function getHeadwordListByPrefix($prefix = "a") {
		
		try {
			// Get SQL (MySQL) handle.
			$sql = $this->getSQLHandle();
			// numeroalkuiset hakusanat eri kyselyllä
			if ($prefix == "0") {
				$query = $sql->prepare("SELECT * FROM hakusanat WHERE hakusana ~ '^[0-9]+' LIMIT 100");
			}
			else { // aakkosilla alkavat
				$query = $sql->prepare("SELECT * FROM hakusanat WHERE LOWER(hakusana) LIKE CONCAT(?, '%') LIMIT 100");
				// Bind a variable to the query.
				$query->bindValue(1, $prefix);
			}
			// Set fetch mode to object.
			// Set a fetch mode and execute the query.
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$query->execute();
			$result = $query->fetchAll();

			return $result;
			
		} catch (PDOException $e) {

			throw new QuerySQLException($e);
		
		} catch (DICTException $e) {
		
		}
			
	}


	// Uusi funktio / 27.5.2026 JV
	// Hakee tietokannasta alkukirjaimen perusteella KAIKKIEN aakkosten ensimmäiset hakusanat (aakkoslinkkejä varten)
	// Muokattu PostgreSQL:ää varten / 20.10.2026 OL
	public function getHeadwordFirstAllByPrefix() {

		$prefixes = ['0', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 
					'p', 'q', 'r', 's', 't', 'u', 'v', 'x', 'y', 'z', 'å', 'ä', 'ö'];
		$placeholders = implode(',', array_fill(0, count($prefixes), '?'));

		$aakkos_query = "SELECT * FROM 
						(SELECT DISTINCT ON (
        					CASE
            					WHEN hakusana ~ '^[0-9]' THEN '0'
            					ELSE lower(left(hakusana, 1))
        					END
							) *
    					FROM hakusanat
    					WHERE (
        					CASE
            					WHEN hakusana ~ '^[0-9]' THEN '0'
            					ELSE lower(left(hakusana, 1))
        					END
    					) IN ($placeholders)
    					ORDER BY
        					CASE
            					WHEN hakusana ~ '^[0-9]' THEN '0'
            					ELSE lower(left(hakusana, 1))
        					END,
        					id
						) ekathakusanat
						ORDER BY id;";
		
		try
		{
			// Get SQL (MySQL) handle.
			$sql = $this->getSQLHandle();
			$query = $sql->prepare($aakkos_query);
			// Set a fetch mode and execute the query with the prefixes as parameters.
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$query->execute($prefixes);
			$result = $query->fetchAll();
			return $result;			
		}
		catch (PDOException $e) {

			throw new QuerySQLException($e);

		}
		catch (DICTException $e) {
		}
	}

	
	/*
		PIKAHAKU
	*/

	// Uusi funktio / 7.11.2019 OL
	// Pikahaun tulosten hakeminen sivupalkkiin, +-100 hakusanaa
	public function getQuickSearchResults($prefix, $list_id) {

		try {		
			// Get SQL (MySQL) handle.
			$sql = $this->getSQLHandle();

			// Erikoismerkkien ja jokerimerkkien käsittely / 2.1.2019 OL
			list($hakusana, $count) = $this->convertWildcards($prefix);
			// Tehdään haku hakumuodoista sanan alkuosalla, jos jokerimerkkejä ei ole käytetty / 14.5.2025 OL
			if ($count > 0) {
				// jokerimerkkejä käytetty: haku kokonaisella sanalla
				//$hakumuoto = '%#' . $hakusana . '#%';
				$hakumuoto = $hakusana;
			} else {
				// ei jokerimerkkejä: haku alkuosalla
				//$hakumuoto = '%#' . $hakusana . '%';
				$hakumuoto = $hakusana . '%';
			}

			// haetaan tuloksia sivupalkkiin ja numeroidaan hakutulos (id) / 6.11.2019 OL
			// haku hakumuodoista / 14.5.2025 OL
			/*$query = $sql->prepare("SELECT t.id, t.ks_id, t.tyyppi, t.hakusana, t.hakumuoto, t.homonyyminro
			FROM (SELECT @rownum:=0) AS r
			JOIN (SELECT h.ks_id, h.tyyppi, h.hakusana, h.hakumuoto, h.homonyyminro, @rownum:=@rownum + 1 AS id 
			FROM hakusanat AS h
			WHERE h.hakumuoto LIKE ?) AS t
			WHERE t.id > ? - 100 AND t.id < ? + 100");
			*/
			$query = $sql->prepare("SELECT t.id, t.ks_id, t.tyyppi, t.hakusana
									FROM (
    									SELECT
        									row_number() OVER (ORDER BY h.id) AS id,
        									h.ks_id, h.tyyppi, h.hakusana
    									FROM hakusanat h
    									WHERE EXISTS (
											SELECT 1 FROM hakumuodot hm
											WHERE hm.ks_id = h.ks_id AND hm.hakumuoto LIKE ?
										)
									) t
									WHERE t.id > ? - 100 AND t.id < ? + 100;");

			// haetaan hakumuodolla / 14.5.2025 OL
			$query->bindValue(1, $hakumuoto);			
			$query->bindValue(2, $list_id);			
			$query->bindValue(3, $list_id);			

			// Set a fetch mode and execute the query.
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$query->execute();
			$result = $query->fetchAll();
			
			return $result;
		
		} catch (QuerySQLException $e) {
			
		} catch (DICTException $e) {
			
		}
		
	}

	// Siirretty Modelista tänne 2.1.2020 OL
	// Palauttaa pikahaun tulosten lukumäärän
	public function getQuickSearchResultsCount($keyword) {
		
		try {
		
			// Get SQL handle.
			$sql = $this->getSQLHandle();

			// Erikoismerkkien ja jokerimerkkien käsittely / 2.1.2019 OL
			list($hakusana, $count) = $this->convertWildcards($keyword);

			// Tehdään haku sanan alkuosalla vain jos jokerimerkkejä ei ole käytetty
			// haetaan hakumuodoista / 19.6.2023 OL
			if ($count > 0) {
				// jokerimerkkejä käytetty: haku kokonaisella sanalla
				//$hakumuoto = '%#' . $hakusana . '#%';
				$hakumuoto = $hakusana;
			} else {
				// ei jokerimerkkejä: haku alkuosalla
				//$hakumuoto = '%#' . $hakusana . '%';
				$hakumuoto = $hakusana . '%';
			}
			//$query = $sql->prepare("SELECT COUNT(*) AS cnt FROM hakusanat WHERE hakumuoto LIKE ?");
			$query = $sql->prepare("SELECT COUNT(DISTINCT h.ks_id) AS cnt
									FROM hakusanat h
									JOIN hakumuodot hm
									  ON hm.ks_id = h.ks_id
									WHERE hm.hakumuoto LIKE ?");

			// haetaan hakumuodolla / 14.5.2025 OL
			$query->bindValue(1, $hakumuoto);
			
			// Set a fetch mode and execute the query.
			$query->setFetchMode(PDO::FETCH_OBJ);
			$query->execute();
			
			// Return the total count of rows as an integer.
			return (int) $query->fetch()->cnt;
		
		} catch (QuerySQLException $e) {
			
		} catch (DICTException $e) {
			
		}

	}

	// Palauttaa pikahaun ehdotukset, 15 ensimmäistä
	// Palautetaan vain hakusana ja tyyppi / 20.2.2020 OL
	public function getQuickSearchAjaxResults($keyword, $type, $offset = 0) {
		
		try {
		
			// Get SQL (MySQL) handle.
			$sql = $this->getSQLHandle();

			// Erikoismerkkien ja jokerimerkkien käsittely / 2.1.2019 OL
			list($ehdotus, $count) = $this->convertWildcards($keyword);

			// Tehdään haku sanan alkuosalla vain jos jokerimerkkejä ei ole käytetty
			if ($count > 0) {
				// jokerimerkkejä käytetty: haku kokonaisella sanalla
				//$ehdotus = '%#' . $ehdotus . '#%';
			} else {
				// ei jokerimerkkejä: haku alkuosalla
				//$ehdotus = '%#' . $ehdotus . '%';
				$ehdotus = $ehdotus . '%';
			}
			// haetaan hakumuodoista / 14.5.2025 OL
			//$query = $sql->prepare("SELECT hakusana,tyyppi,hakumuoto FROM hakusanat WHERE hakumuoto LIKE ? GROUP BY hakusana COLLATE utf8_bin ORDER BY id LIMIT 15");
			$query = $sql->prepare("SELECT h.hakusana, h.tyyppi FROM hakusanat h
									WHERE EXISTS (
    									SELECT 1 FROM hakumuodot hm
    									WHERE hm.ks_id = h.ks_id AND hm.hakumuoto LIKE ?
									)
									ORDER BY h.id
									LIMIT 15;");

			// Bind a keyword to the query.
			$query->bindValue(1, $ehdotus);
			
			// Set a fetch mode and execute the query.
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$query->execute();
			
			// Return all results.
			return $query->fetchAll();
		
		} catch (QuerySQLException $e) {
			
		} catch (DICTException $e) {
			
		}
		
	}
	
	// Pikahaun ensimmäisen osuman tietojen palauttaminen (käytetään vain kerran?)
	// Palauttaa hakusanan tiedot merkkijonon perusteella
	public function getFirstWordByPrefix($prefix) {

		try {
			
			// Get SQL handle from the base model.
			$sql = $this->getSQLHandle();

			// Erikoismerkkien ja jokerimerkkien käsittely / 2.1.2019 OL
			list($hakusana, $count) = $this->convertWildcards($prefix);

			// Tehdään haku sanan alkuosalla vain jos jokerimerkkejä ei ole käytetty
			// haetaan hakumuodoista / 14.5.2025 OL
			if ($count > 0) {
				// jokerimerkkejä käytetty: haku kokonaisella sanalla
				//$hakumuoto = '%#' . $hakusana . '#%';
				$hakumuoto = $hakusana;
			} else {
				// ei jokerimerkkejä: haku alkuosalla
				//$hakumuoto = '%#' . $hakusana . '%';
				$hakumuoto = $hakusana . '%';
			}

			//$query = $sql->prepare("SELECT * FROM hakusanat WHERE hakumuoto LIKE ? LIMIT 1");
			$query = $sql->prepare("SELECT h.* FROM hakusanat h
									JOIN hakumuodot hm
									  ON hm.ks_id = h.ks_id
									WHERE hm.hakumuoto LIKE ?
									ORDER BY h.id
									LIMIT 1;");

			// haetaan hakumuodolla / 14.5.2025 OL
			//$query->bindValue(1, $hakusana);
			$query->bindValue(1, $hakumuoto);
			
			// Set fetch mode to object.
			$query->setFetchMode(PDO::FETCH_OBJ);
			
			// Execute query
			$query->execute();

			// Get the row data.
			$res = $query->fetch();
			
			return $res;
		
		} catch (QuerySQLException $e) {

		} catch (DICTException $e) {
			
		}

	}

	//
	public function getExactHeadword($hakusana) {

		try {
		
			// Get SQL handle from the base model.
			$sql = $this->getSQLHandle();

			// Prepare query
			$query = $sql->prepare("SELECT * FROM hakusanat WHERE hakusana = ? LIMIT 1");
			$query->bindValue(1, $hakusana);
			
			// Set fetch mode to object.
			$query->setFetchMode(PDO::FETCH_OBJ);
			
			// Execute query
			$query->execute();

			// Get the row data.
			$res = $query->fetch();
			
			return $res;
		
		} catch (QuerySQLException $e) {
			
		} catch (DICTException $e) {
			
		}

	}

	// Pikahaun jokerimerkkien käsittely ja muuntaminen SQL-hakua varten
	public function convertWildcards($keyword) {
		
		$korvaa = array('/\*/', '/\?/');
		$jokerit = array('%', '_');		
		$count = 0;
		$hakusana = preg_replace($korvaa, $jokerit, trim($keyword), -1, $count);

		return array($hakusana, $count);
	}


	
	/*
		TARKENNETTU HAKU
	*/

	// Tarkennetun haun tulosten hakeminen
	public function getAdvancedSearchResults($orig_query) {

		try {
		
			$xml = $this->getXMLHandle();
			
			//var_dump($orig_query);

			$results = $xml->queryRESTAPI($orig_query);
			
			//var_dump($results);

			unset($xml);

			return $results;

		} catch (QueryXMLException $e) {
			
		} catch (DICTException $e) {
			
		}
		
	}
	
	// Palauttaa tarkennetun haun tulosten lukumäärän ja sessionid:n
	public function getAdvancedSearchResultsCount($orig_query) {
		
		try {
		
			// Get XML handle.
			$xml = $this->getXMLHandle();
			
			//Eexecute the query.
			$result = $xml->queryRESTAPI($orig_query);
			
			//$firstline = substr($result, 0, strpos($result, PHP_EOL));
			$firstline = substr($result, 0, strpos($result, 'exist:execution-time'));
			preg_match('/exist:hits=\"(\d+)\"/', $firstline, $count);
			preg_match('/exist:session=\"(\d+)\"/', $firstline, $sessionid);

			unset($xml);

			if (!isset($count[1])) {
				$count[1] = 0;
			}
			if (!isset($sessionid[1])) {
				$sessionid[1] = 0;
			}
			return array("count" => $count[1], "sessionid" => $sessionid[1]);
		
		} catch (QueryXMLException $e) {
			
		} catch (DICTException $e) {
			
		}
		
	}

	// UUSI FUNKTIO / 29.1.2020 OL
	// Muodostaa XQueryn eXistin REST API -hakua varten
	public function getRestWrappedQuery($orig_query, $start = 1, $existSession = 0) {

		global $cfg; // Get global configurations

		// Use session-id, if available
		if ($existSession != 0) { $sessionattr = " session='$existSession'"; } 
		else { $sessionattr = ""; }
		
		// Calculate start position and maximum results
		$start = $start - 100;
		$max = 200;
		if ($start <= 0) { $start = 1; }

		// Compile xquery for REST API
		$begin = "<query xmlns='http://exist.sourceforge.net/NS/exist' 
					start='". $start . "' max='" . $max . "' 
					cache='yes' wrap='yes'" . $sessionattr . ">
				<text><![CDATA[
				xquery version '3.1';
				declare default collation 'http://www.w3.org/2013/collation/UCA?lang=fi-FI;strength=secondary;alternate=shifted';
				declare namespace output = 'http://www.w3.org/2010/xslt-xquery-serialization';
				declare option output:method 'json';
				declare option output:media-type 'application/json';
				";
		$end = "]]></text></query>";
		$collection = "collection('" . $cfg['XML']['DATABASE'] . "')";
		$restquery = $begin . 
					"let \$articles := for \$a at \$nro in " . $collection . $orig_query .
					"order by \$a//SearchForm[1]
					 return \$a
					 for \$a at \$nro in \$articles
					 return serialize(
					 map{'id' : \$nro,
						'ks_id' : \$a/xs:string(@id), 
						'tyyppi' : \$a/xs:string(@style),
						'hakusana' : \$a//SearchForm[1]/text(),
						'homonyyminro' : \$a/xs:string(@homographNumber),
						'sanaluokka' : string-join(\$a//HeadwordCtn/PartOfSpeech, ', ')}, 
						<output:serialization-parameters>
							<output:method>json</output:method>
							<output:media-type>application/json</output:media-type>
						</output:serialization-parameters>)" . 
					$end;
	
		// DEBUG
		//var_dump($restquery);

		return $restquery;
	}


	public function getSelectOptions($id) {

		global $cfg;

		foreach ($cfg[$id] as $key => $val) {

			$temp[$key] = $val;

		}

		return $temp;

	}

	// XQueryn koostaminen tarkennetun haun lomakkeen tiedoista
	//
	// UUSI osumien korostustapa / 27.4.2021 OL
	//
	public function getAdvancedSearchQuery($keyword, $from, $word_group, $usage_scope, $tindex, $avindex) {
		global $cfg;

		$query = "";
		
		// poistetaan muut erikoismerkit, koska niitä ei voi hakea / 27.10.2026 OL
		$keyword = preg_replace('/[^\p{L}\p{N}\s*?]/u', '', $keyword);
		// tarkistetaan, ettei hakuun jäänyt pelkkiä jokerimerkkejä (* ja ?)
		if (preg_match('/^[\*\?]+$/', $keyword)) { $keyword = ''; }
		// Replace * => .* and ? => . for regular expression search.
		$keyword = str_replace(['*', '?'],['.*', '.'], $keyword);
		
		if ($keyword and $from == 1) {
			// Search for all headword
			$_SESSION['query']['keyword'] = "[.//HeadwordCtn[ft:query(./SearchForm,<query><regex>$keyword</regex></query>)]]";
			$query .= $_SESSION['query']['keyword'];

/* Poistettu osahaku käytöstä, ainakin toistaiseksi / 21.9.2026 OL
		} elseif ($keyword and $from == 2) {
			// Search for compound parts of headword
			$_SESSION['query']['keyword'] = "[.//Word[ft:query(.,<query><regex>$keyword</regex></query>)]]";			
			$query .= $_SESSION['query']['keyword'];
*/
/* Muukattu yhdyssanahaku samanlaiseksi (epäloogiseksi) kuin aiemmassa versiossa / 21.9.2026 OL
		} elseif ($keyword and $from == 3) {
			// Search for compound headword
			$_SESSION['query']['keyword'] = "[@freeType='compound'][ft:query(.//SearchForm,<query><regex>$keyword</regex></query>)]";
			$query .= $_SESSION['query']['keyword'];
*/
		} elseif ($keyword and $from == 3) {
			// Search for compound headword
			$_SESSION['query']['keyword'] = "[@freeType='compound'][ft:query(.//Prefix | .//Stem | .//Word, <query><regex>$keyword</regex></query>)]";
			$query .= $_SESSION['query']['keyword'];

		} elseif ($keyword and $from == 4) {
			// Search for non-compound headword
			$_SESSION['query']['keyword'] = "[@freeType='monoWord'][ft:query(.//SearchForm,<query><regex>$keyword</regex></query>)]";
			$query .= $_SESSION['query']['keyword'];

		} else {
			// TODO: do something if $from wasn't set at all.
		}

		if ($word_group) {

			$_SESSION['query']['word_group'] = "[ft:query(.//PartOfSpeech,<query><regex>$word_group</regex></query>)]";
			$query .= $_SESSION['query']['word_group'];
			
		}
			
		if ($usage_scope) {

			$_SESSION['query']['usage_scope'] = "RangeOfApplication[ft:query(.,<query><regex>$usage_scope</regex></query>)]";
			$query .= "[.//" . $_SESSION['query']['usage_scope'] . "]";
			
		}

		if ($tindex) {

			$_SESSION['query']['tindex'] = "[ft:query(.//InflectionScheme,<query><regex>$tindex</regex></query>)]";
			$query .= $_SESSION['query']['tindex'];

		}

		if ($avindex) {

			$_SESSION['query']['avindex'] = "[ft:query(.//InflectionScheme,<query><regex>$avindex</regex></query>)]";
			$query .= $_SESSION['query']['avindex'];

		}

		if (empty($query)) {
			$query = null;
		} else {
			// xQuery-yhdistelmähaku kokonaisuudessaan
			$query = "//DictionaryEntry" . $query;	
		}

		return $query;
		
	}

	/*
		KWIC-HAKU (FULLTEXT)
		UUSIA FUNKTIOITA / 7.7.2026 OL
	*/

	// XQueryn koostaminen
	public function getKwicSearchQuery($fulltext, $fullfrom, $lemma) {
		global $cfg;

		$query = "";
		
		// poistetaan muut erikoismerkit, koska niitä ei voi hakea / 27.10.2026 OL
		$fulltext = preg_replace('/[^\p{L}\p{N}\s*?]/u', '', $fulltext);
		// tarkistetaan, ettei hakuun jäänyt pelkkiä jokerimerkkejä (? ja *)
		if (preg_match('/^[\*\?]+$/', $fulltext)) { $fulltext = ''; }
		// Replace * => .* and ? => . for regular expression search.
		$fulltext = str_replace(['*', '?'],['.*', '.'], $fulltext);

		
		if ($fulltext and $fullfrom == 1) {
			// Haku koko artikkelista
			if ($lemma) {
				$_SESSION['query']['fulltext'] = "//w[ft:query(. | ./@lemma,<query><regex>$fulltext</regex></query>)]";
			} else {
				$_SESSION['query']['fulltext'] = "//w[ft:query(.,<query><regex>$fulltext</regex></query>)]";
			}
			$query = "//DictionaryEntry" . $_SESSION['query']['fulltext'];

		} elseif ($fulltext and $fullfrom == 2) {
			// Haku selitteistä
			if ($lemma) {
				$_SESSION['query']['fulltext'] = "//Definition//w[ft:query(. | ./@lemma,<query><regex>$fulltext</regex></query>)]";
			} else {
				$_SESSION['query']['fulltext'] = "//Definition//w[ft:query(.,<query><regex>$fulltext</regex></query>)]";
			}
			$query = $_SESSION['query']['fulltext'];

		} elseif ($fulltext and $fullfrom == 3) {
			// Haku esimerkeistä
			if ($lemma) {
				$_SESSION['query']['fulltext'] = "//Example//w[ft:query(. | ./@lemma,<query><regex>$fulltext</regex></query>)]";
			} else {
				$_SESSION['query']['fulltext'] = "//Example//w[ft:query(.,<query><regex>$fulltext</regex></query>)]";
			}
			$query = $_SESSION['query']['fulltext'];

		} else {
			// TODO: do something if $fullfrom wasn't set at all.
			$query = "";
		}

		if (empty($query)) {
			$query = null;
		} 

		return $query;
		
	}

	// Palauttaa KWIC-haun tulosten lukumäärän ja sessionid:n
	public function getKwicSearchResultsCount($result) {
		
        $xml = simplexml_load_string($result);

        $json = (string)$xml->children('http://exist.sourceforge.net/NS/exist')->value;

        $data = json_decode($json, true);

        return array("count" => $data['count'], "sessionid" => $data['sessionid']);		
	}
	
	// Muodostaa XQueryn eXistin REST API -hakua varten
	public function getKwicRestWrappedQuery($orig_query, $fullfrom = 1, $start = 1, $existSession = 0) {

		global $cfg; // Get global configurations
		
		// haun konteksti
		switch ($fullfrom) {
			case 2:
				//$context = "ancestor::Definition"; // selite
				$context = "let \$parent := \$hit/ancestor::Definition";
				break;
			case 3:
				//$context = "ancestor::Example"; // esimerkki
				$context = "let \$parent := \$hit/ancestor::Example";
				break;
			default:
				//$context = "parent::node()"; // osuman isäntäelementti
				$context = "let \$ancestor := (\$hit/ancestor::*[self::Headword or self::Definition or self::Example or self::SeeAlso or self::See])[1] 
    					let \$parent := if (\$ancestor) then \$ancestor else \$hit/parent::node()";

		}

		// tulosten määrä sivulla ja aloituspaikka
		$max = 10;
		if ($start <= 0) { $start = 1; }

		// Compile xquery for REST API
		$begin = "<query xmlns='http://exist.sourceforge.net/NS/exist'>
				<text><![CDATA[
				xquery version '3.1';
				import module namespace kwic='http://exist-db.org/xquery/kwic';
				declare default collation 'http://www.w3.org/2013/collation/UCA?lang=fi-FI;strength=secondary;alternate=shifted';
				declare namespace cache = 'http://exist-db.org/xquery/cache';
				declare namespace output = 'http://www.w3.org/2010/xslt-xquery-serialization';
				declare option output:method 'json';
				declare option output:media-type 'application/json';
				declare function local:filter(\$node as node(), \$mode as xs:string) {
    				if (\$mode eq 'before') then text { concat(\$node, ' ') }
    				else \$node
				};
				declare function local:mark-hits(\$node as node(), \$hit as node()) as node() {
  					typeswitch(\$node)
						case text()
      						return \$node
					    case element()
					      return
        					if (\$node is \$hit)
        					then
          						<exist:match>{\$node/node()}</exist:match>
        					else
          						element {node-name(\$node)} {
            						\$node/@*,
            						for \$child in \$node/node()
            						return local:mark-hits(\$child, \$hit)
          						}
				   		default
      						return \$node
				};
				";
		$end = "]]></text></query>";
		$collection = "collection('" . $cfg['XML']['DATABASE'] . "')";
		if ($existSession == 0) { 
			$restquery = $begin . 
					"let \$hits := for \$h in " . $collection . $orig_query . 
					" 	let \$hw := \$h/ancestor-or-self::DictionaryEntry/HeadwordCtn/Headword[1] 
					  	order by \$hw 
					  	return 
				  	        map {
                				'doc' : document-uri(root(\$h)),
                				'node-id' : util:node-id(\$h)
            				}
					let \$sid := util:hash(xs:string('" . $orig_query . "'), 'MD5')
					let \$_ := cache:put('ks2026', \$sid, \$hits)
					return serialize(
						map{'count' : count(\$hits),
							'sessionid' : \$sid
						},
						map{
							'method' : 'json'
						})" . 
					$end;
		} else {
			$restquery = $begin . 
					"let \$allHits := cache:get('ks2026', '" . $existSession . "')
					for \$item at \$nro in subsequence(\$allHits, " . $start . ", " . $max . ") 
					    let \$doc := doc(xs:string(\$item?doc))
    					let \$hit := util:node-by-id(\$doc, \$item?node-id)
    					" . $context . 
    					" let \$osuma := if (name(\$parent)='Headword') then <osuma/> else <osuma>{local:mark-hits(\$parent, \$hit)}</osuma>
						let \$de := \$hit/ancestor-or-self::DictionaryEntry
					return serialize(
					  	map{'id' : \$nro + " . $start . " - 1,
							'ks_id' : \$de/xs:string(@id), 
							'hakusana' : string(\$de/HeadwordCtn/Headword[1]),
							'kwic' : kwic:summarize(\$osuma, <config width='40'/>)
						},
						map{
							'method' : 'json',
							'media-type' : 'application/json'
						})" . 
					$end;
		}
		// DEBUG
		//echo "<pre>";
		//echo $restquery;
		//echo "</pre>";

		return $restquery;
	}
	// Tarkistetaan, ovatko KWIC-haun tulokset vielä välimuistissa / 15.9.2026 OL
	public function checkKwicCache($existSession) {
		
		$xml = $this->getXMLHandle();

		$checkquery = "<query xmlns='http://exist.sourceforge.net/NS/exist'>
				<text><![CDATA[
				xquery version '3.1';
				import module namespace cache = 'http://exist-db.org/xquery/cache';
				declare namespace output = 'http://www.w3.org/2010/xslt-xquery-serialization';
				declare option output:method 'json';
				declare option output:media-type 'application/json';
				let \$sid := '" . $existSession . "' 
				return 
					if (exists(cache:get('ks2026', \$sid))) then
						serialize(
							map { 'found' : true() },
							map { 'method' : 'json' }
						)
					else
						serialize(
							map { 'found' : false() },
							map { 'method' : 'json' }
						)
				]]></text></query>";
		
        $result = $xml->queryRESTAPI($checkquery);
        $parsed = simplexml_load_string($result);
        $json = (string)$parsed->children('http://exist.sourceforge.net/NS/exist')->value;
        $response = json_decode($json, true);

		return $response['found'];
	}
}

?>
