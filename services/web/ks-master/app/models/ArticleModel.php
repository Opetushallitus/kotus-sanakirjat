<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * app/models/ArticleModel.php
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * 				Outi Lehtinen ja Jari Vihtari, 2020
*/

class ArticleModel extends Model {

	// Artikkelin hakeminen XML-tietokannasta ja palautus HTML-muodossa
	//
	// UUSI osumien korostustapa / 27.4.2021 OL
	//
	public function getWordArticle($id, $mword="", $viewtype = "full") {

		global $cfg; // Get global configurations

		try {
		
			$xml = $this->getXMLHandle();
			
			// Check wheter article's view type need to be changed.
			switch ($viewtype) {				
				case "inflection":
					break;
				case "examples":
					break;
				case "full":
				default:
					$viewtype = "full";
					break;
			}

			// Start building up query.
			$query = "let \$a :=//DictionaryEntry[@id='" . $id . "'] ";
			$korostettavat = array();
			
			// käyttöala
			if (isset($_SESSION['query']['usage_scope']) and isset($_GET['hilight'])) {
				$query .= "let \$ka := for \$s in \$a//" . $_SESSION['query']['usage_scope'] . " return util:node-id(\$s) ";
				$korostettavat[] = "\$ka";
			}
			// selite
			if (isset($_SESSION['query']['meaning']) and isset($_GET['hilight'])) {
				$query .= "let \$sel := for \$s in \$a//" . $_SESSION['query']['meaning'] . " return util:node-id(\$s) ";
				$korostettavat[] = "\$sel";
			}
			// esimerkki
			if (isset($_SESSION['query']['example']) and isset($_GET['hilight'])) {
				//$query .= $_SESSION['query']['example'];
				$query .= "let \$esim := for \$e in \$a//" . $_SESSION['query']['example'] . " return util:node-id(\$e) ";
				$korostettavat[] = "\$esim";
			}
			
			// kokotektihaussa voidaan korostaa normaalisti?
/*			if (isset($_SESSION['query']['fulltext']) and isset($_GET['hilight'])) {
				$query = "//DictionaryEntry[@id='" . $id . "']" . $_SESSION['query']['fulltext'];
				$result = $xml->queryRESTXQarticle($query, $viewtype);
			}
			// muuten korostetaan elementti, josta sana löytyi
			else {
*/
				$query .= " return (" . implode(",", $korostettavat) . ")";
				//var_dump($query);
				$result = $xml->queryRESTXQarticleMatches($id, $query, $viewtype);
/*			}*/

			//var_dump($result);

			// Check wheter a query has failed or returned no results.
			if (!$result) {
				throw new DICTException(_("ArticleNotFound_Caption"), 
					_("ArticleNotFound_Message_Begin") .
					_("ArticleNotFound_Message_End")
				);				
			}
			
			unset($xml);
			
			// Return article XML data.
			return $result;
		
		} catch (QueryXMLException $e) { 

		} catch (DICTException $e) {
			
		}
		
	}

	// Artikkelin hakeminen XML-tietokannasta ja palautus HTML-muodossa
	//
	// UUSI osumien korostustapa / 27.4.2021 OL
	//
	public function getKwicWordArticle($id, $osumanro = 1, $viewtype = "full") {

		global $cfg; // Get global configurations

		try {
		
			$xml = $this->getXMLHandle();
			
			// Check wheter article's view type need to be changed.
			switch ($viewtype) {				
				case "inflection":
					break;
				case "examples":
					break;
				case "full":
				default:
					$viewtype = "full";
					break;
			}
			$context = "nocontext";
			$hakusana = "nolemma";
			$query_fulltext = isset($_SESSION['query']['fulltext']) ? $_SESSION['query']['fulltext'] : ''; // sessiomuuttuja muuttujaan, jos sessiomuuttujaa ei olekaan 18.9.2026 JV
			preg_match('/<regex>(.*?)<\/regex>/', $query_fulltext, $match);
			$hakusana = $match[1];

			// Artikkelihaku ja osumien korostus / 18.9.2026 OL
			$korostettavat = array();
			$query = "let \$elem := for \$s in //DictionaryEntry[@id='" . $id . "']" . 
						$query_fulltext . " return util:node-id(\$s) ";
			$korostettavat[] = "\$elem";
			$query .= " return (" . implode(",", $korostettavat) . ")";
			var_dump($query);
			$result = $xml->queryRESTXQarticleMatches($id, $query, $viewtype);

			//echo '<pre>';
			//echo htmlspecialchars($result);
			//echo '</pre>';

			// Check wheter a query has failed or returned no results.
			if (!$result) {
				throw new DICTException(_("ArticleNotFound_Caption"), 
					_("ArticleNotFound_Message_Begin") .
					_("ArticleNotFound_Message_End")
				);				
			}
			
			unset($xml);
			
			// Return article XML data.
			return $result;
		
		} catch (QueryXMLException $e) { 

		} catch (DICTException $e) {
			
		}
		
	}

	// Hakusanalistan oikean kohdan noutamiseen tietokannasta sanan perusteella 
	public function getHeadwordListIndexByWord($word) {
		
		try {
			// Get SQL (MySQL) handle.
			$sql = $this->getSQLHandle();
			
			// Tarkistetaan onko homonyyminumero / 13.2.2020 OL
			// Homonyyminumeroita ei käytetä? / 28.10.2024 OL
			//$head = explode(':', $word, 2);
			// Haku
			//if ($head[1]) {
			//	$query = $sql->prepare("SELECT id FROM hakusanat WHERE hakusana LIKE CONCAT(?, '%') AND homonyyminro=? LIMIT 1");
			//	$query->bindValue(1, $head[0]);
			//	$query->bindValue(2, $head[1]);
			//} else {
				$query = $sql->prepare("SELECT id FROM hakusanat WHERE hakusana LIKE CONCAT(?, '%') LIMIT 1");
				$query->bindValue(1, $word);
			//}
			// Set a fetch mode and execute the query.
			$query->setFetchMode(PDO::FETCH_NUM);
			$query->execute();

			$result = $query->fetchAll();

			return $result[0][0];
			
		} catch (QuerySQLException $e) {
		
		} catch (DICTException $e) {
		
		}
			
	}

	// UUSI 18.11.2019 OL - Käytetään vain kerran, aakkosselauksessa!
	// Palauttaa sanakirja-id:n tietokanta-id:n perusteella
	public function getArticleIdById($id) {
		
		try {
		
			// Get SQL (MySQL) handle.
			$sql = $this->getSQLHandle();
			
			// Prepare a query for execution.
			$query = $sql->prepare("SELECT * FROM hakusanat WHERE id = ? LIMIT 1");
			
			// Bind a variable into it.
			$query->bindValue(1, $id);
			
			// Set fetch mode to object.
			$query->setFetchMode(PDO::FETCH_OBJ);
			
			// Execute query.
			$query->execute();
			
			// Return the word data.
			return $query->fetch();
		
		} catch (QuerySQLException $e) {
			
		} catch (DICTException $e) {
			
		}
		
	}

	public function getArticlePath($ks_id, $word) {

		// p=article&dict_id=xxx&word=yyy
	
		return "p=article&ks_id=" . $ks_id . "&word=" . $word;
	}

	// Pysyvä linkki artikkeliin / 1.10.2026 OL
	public function getArticleHeadword($ks_id) {
	
		try {
		
			// Get SQL (MySQL) handle.
			$sql = $this->getSQLHandle();
			
			// Prepare a query for execution.
			$query = $sql->prepare("SELECT hakusana FROM hakusanat WHERE ks_id = ? LIMIT 1");
			
			// Bind a variable into it.
			$query->bindValue(1, $ks_id);
			
			// Set fetch mode to object.
			$query->setFetchMode(PDO::FETCH_OBJ);
			
			// Execute query.
			$query->execute();
			$result = $query->fetch();

			return $result->hakusana;
		
		} catch (QuerySQLException $e) {
			
		} catch (DICTException $e) {
			
		}
		
	}
	
	// KS: Päivän sana
	public function getArticleOfDay() {

		try {

			// Get SQL (MySQL) handle.
			$sql = $this->getSQLHandle();
			
			// Execute query to fetch the *actual* article count.
			$query = $sql->prepare("SELECT COUNT(*) AS count FROM hakusanat WHERE tyyppi = 'H'");

			// Set fetch mode to object.
			$query->setFetchMode(PDO::FETCH_OBJ);

			// Execute query.
			$query->execute();

			// Get the actual row count.
			$count = intval($query->fetch()->count);

			// Use the below algorithm to calculate the word to be shown.
			//$limit = ((date('j') * 99999999) / 0.02) * date('Y') % $count;

			// Calculate the word to be shown with a seed number.
			// Offers way better randomization than the remainder above.
			// Päivän sana vaihdetaan klo 01:01 / OL 28.2.2024
			srand(mktime(0, 1, 1, date('n'), date('j'), date('Y')));
			$limit = rand(1, $count);

			// Prepare query to fetch the word of the day.
			$query = $sql->prepare("SELECT hakusana, ks_id, homonyyminro FROM hakusanat WHERE tyyppi = 'H' LIMIT ?,1");
			
			// Bind a variable into it.
			$query->bindValue(1, $limit, PDO::PARAM_INT);

			// Set fetch mode to object.
			$query->setFetchMode(PDO::FETCH_OBJ);
			
			// Execute query.
			$query->execute();

			// Get the word data.
			return $query->fetch();

		} catch (QuerySQLException $e) {
			
		} catch (DICTException $e) {
			
		}

	}

}

?>
