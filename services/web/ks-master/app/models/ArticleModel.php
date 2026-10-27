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
	public function getWordArticle(string $id, string $mword='', string $viewtype = 'full'): ?string {

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
	public function getKwicWordArticle(string $id, int $osumanro = 1, string $viewtype = 'full'): ?string {

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
	public function getHeadwordListIndexByWord(string $word): ?int {
		
		try {
			// Get SQL (MySQL) handle.
			$sql = $this->getSQLHandle();
			
			    $query = $sql->prepare("SELECT id FROM hakusanat WHERE hakusana ILIKE ? ORDER BY id LIMIT 1");

    			$query->execute([$word . '%']);

    			$id = $query->fetchColumn();

    			return $id !== false ? (int)$id : null;
			
		} catch (QuerySQLException $e) {
		
		} catch (DICTException $e) {
		
		}
			
	}

	public function getArticlePath(string $ks_id, string $word): ?string {

		// p=article&dict_id=xxx&word=yyy
	
		return "p=article&ks_id=" . $ks_id . "&word=" . $word;
	}

	// Pysyvä linkki artikkeliin / 1.10.2026 OL
	public function getArticleHeadword(string $ks_id): ?string {
	
		try {
		
			// Get SQL (MySQL) handle.
			$sql = $this->getSQLHandle();
			
			// Prepare a query for execution.
			$query = $sql->prepare("SELECT hakusana FROM hakusanat WHERE ks_id = ? LIMIT 1");
			// Bind a variable into it.
			$query->bindValue(1, $ks_id);
			
			// Execute query.
			$query->execute();
			$hakusana = $query->fetchColumn();

			return $hakusana !== false ? (string)$hakusana : null;
		
		} catch (QuerySQLException $e) {
			
		} catch (DICTException $e) {
			
		}
		
	}
	
}

?>
