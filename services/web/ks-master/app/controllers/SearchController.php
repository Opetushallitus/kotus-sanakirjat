<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * app/controllers/SearchController.php
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * 				Outi Lehtinen ja Jari Vihtari, 2019
*/

class SearchController extends Controller {

	/*
		Hakusanalista, pikahaku ja tarkennettu haku: aloitussivut
		(index.php)
	*/

	// Hakusanalista / 16.7.2019 OL
	// Ei käytetä enää / 11.6.2020 OL
	// Voisi käyttää KS:n selausnäkymänä / 30.4.2026 OL
	public function showHeadwordList() {
		
		global $cfg;

		// Set a model to be used.
		$model = $this->setModel("searchModel");
		// Set a view to be used.
		$view  = $this->setView("PageView");
		
		// Set a page title.
		$this->set("title", _("Browse_headwords"));

		// Set last update date from the config.
		$this->set("last_update", $cfg['KS']['LAST_UPDATE']);
		
		// Set some scripts being used on the page.
		$this->set("scripts", array("leftbar.js"));
		
		// Render a page to the user (except footer).
		$view->header()->navi();
		echo "<div class=\"palstat\">";
		// vasen palkki
		$view->leftbar();
		// oikeassa palkissa sisältö
		$view->content("BrowseHeadwords");
		echo "</div>";
		$view->footer();

	}


	// Pikahaun tulokset ja ensimmäisen artikkelin näyttäminen
	// funktio kirjoitettu uusiksi / 15.10.2020 OL
	public function showQuickSearchResults() {

		global $cfg; // Get global configurations
		
		// Set a models to be used.
		$amodel = $this->setModel("ArticleModel");
		$smodel = $this->setModel("SearchModel");
		// Set a view to be used.
		$view  = $this->setView("SearchView");
		
		// Check wheter a search form has been submitted. If so, save
		// form data into a session variable and redirect user to the
		// results in order to get rid of the POST-data. This way the
		// user won't have to resubmit form data each time when going
		// back from a word article to the search results list.
		// Uudelleenohjaus siirretty funktion loppuun / 15.10.2020 OL
		if ($_POST) {
			$_SESSION['search'] = $_POST;
		}

		// POST-data on tallennettu suoraan sessioon, siksi sanitoidaan
		// myös SESSION-muuttujat ennen käyttöä / 6.10.2026 JV	
		// Set POST-parameter 'keyword'.
		$this->set("keyword", sanitize_input($_SESSION['search']['keyword'], 'string'));

		// Set a page title - lisätään haettu termi otsikkoon / 11.6.2020 OL
		$this->set("title", _("Search results") . ": " . $this->get("keyword"));

		// last update date from the config.
		$this->set("last_update", $cfg['KS']['LAST_UPDATE']);

		// Hakutulosten lukumäärä
		$count = $smodel->getQuickSearchResultsCount($this->get("keyword"));
		$this->set("count", $count);

		// Ilmoitus, jos hakusanaa ei löydy
		if ($count == 0) {
			throw new DICTException(_("QuickSearchResults_NoResults_Caption"), 
				_("QuickSearchResults_NoResults_Message_Begin")
			);
		}
		
		// Haetaan ensimmäisen osuman id ja hakusana
		$firstWord = $smodel->getFirstWordByPrefix($this->get("keyword"));
		$this->set("ks_id", $firstWord->ks_id);
		
		// ohjataan ensimmäisen osuman näyttävälle sivulle / 15.10.2020 OL
		$locationref = "Location: ./?p=qs-article&ks_id=" . $this->get("ks_id") . "&list_id=1&keyword=" . $this->get("keyword") . "&word=" . $firstWord->hakusana;
		if ($_POST) {
			header($locationref);
		}
	}

	// Haetaan artikkeli pikaosoitteen (permalink) perusteella / 30.9.2026 OL
	public function showArticleFromPermalink() {
		global $cfg; // Get global configurations
		
		// Set a models to be used.
		$model = $this->setModel("SearchModel");
		$view  = $this->setView("SearchView");

		if ($_POST) {
			$_SESSION['search'] = $_POST;
		}
		
		// lisätty sanitointikutsu / 6.10.2026 JV
		$this->set("keyword", sanitize_input($_SESSION['search']['keyword'], 'string'));
		
		// Haetaan ensimmäisen osuman id ja hakusana
		$firstWord = $model->getExactHeadword($this->get("keyword"));

		// Ilmoitus, jos hakusanaa ei löydy
		if ($firstWord === false) {
			throw new DICTException(_("QuickSearchResults_NoResults_Caption"), 
				_("QuickSearchResults_NoResults_Message_Begin")
			);
		}

		$this->set("ks_id", $firstWord->ks_id);
		
		header("Location: ./?p=article&ks_id=" . $this->get("ks_id") . "&word=" . $firstWord->hakusana);
		
	}

	// Tarkennetun haun lomake
	public function showAdvancedSearchForm() {

		global $cfg;

		// Set the model to be used.
		$model = $this->setModel("SearchModel");
		
		// Set the view to be used.
		$view = $this->setView("SearchView");
		
		// Set a page title.
		// Sivupalkin myötä samalla sivulla haku ja selaus (uusi käännös!) / 11.6.2020 OL
		$this->set("title", _("Advanced search and browse"));

		// Set last update date from the config.
		$this->set("last_update", $cfg['KS']['LAST_UPDATE']);

		// Clear queries from previous searches.
		$_SESSION['query'] = null;
		
		// Set some scripts to be used within a page.
		// leftbar.js lisätty, koska sivupalkki / 9.5.2020 OL
		$this->set("scripts", array("advsearch.js", "leftbar.js"));

		$this->set("word_groups", $model->getSelectOptions("WORD_GROUPS"));
		$this->set("usage_scopes", $model->getSelectOptions("USAGE_SCOPES"));
		$this->set("tindex", $model->getSelectOptions("TINDEX"));
		$this->set("avindex", $model->getSelectOptions("AVINDEX"));
				
		// Render a page to the user (except footer).
		$view->header()->navi();
		echo "<div class=\"palstat\">";
		// vasen palkki
		$view->leftbar();
		// oikeassa palkissa sisältö
		$view->content("AdvancedSearchForm");
		echo "</div>";
		$view->footer();

	}

	// Tarkennetun haun tulokset
	public function showAdvancedSearchResults() {
		
		global $cfg; // Get global configurations / 15.1.2020 OL

		// Set the model to be used.
		$model = $this->setModel("SearchModel");
		
		// Set the view to be used.
		$view  = $this->setView("SearchView");

		// Set last update date from the config.
		$this->set("last_update", $cfg['KS']['LAST_UPDATE']);
		
		// Check wheter a search form has been submitted. If so, save
		// form data into a session variable and redirect user to the
		// results in order to get rid of the POST-data. This way the
		// user won't have to resubmit form data each time when going
		// back from a word article to the search results list.
		if ($_POST) {
			
			$_SESSION['advsearch'] = $_POST;
			header("Location: ./?p=advsearchresults");
			
		}

		// Set search-type 17.7.2026 jv
		$_SESSION['advsearchtype'] = "advsearch";
		
		// Set a POST-parameters, lisätty sanitointikutsut / 6.10.2026 JV
		$this->set("keyword", sanitize_input(strtolower($_SESSION['advsearch']['keyword']), 'string'));
		$this->set("hwfrom", sanitize_input($_SESSION['advsearch']['hwfrom'], 'string'));
		$this->set("tindex", sanitize_input($_SESSION['advsearch']['tindex'], 'string'));
		$this->set("avindex", sanitize_input($_SESSION['advsearch']['avindex'], 'string'));
		$this->set("word_group", sanitize_input($_SESSION['advsearch']['word_group'], 'string'));
		$this->set("usage_scope", sanitize_input($_SESSION['advsearch']['usage_scope'], 'string'));
				
		// Check whether used has filled even one essential field.
		if (!$this->get("keyword") && !$this->get("tindex") && !$this->get("avindex") && !$this->get("word_group") && !$this->get("usage_scope")) {

			// Redirect user back to the form and stop execution.
			// This should not happen when user has Javascript enabled
			// as jQuery prevents submitting form unless at least one
			// of the essential fields is filled in.
			header("Location: ./?p=advsearchform");
			exit;
			
		}
	
		// Build up a query to be used with eXist.
		$query = $model->getAdvancedSearchQuery($this->get("keyword", true), $this->get("hwfrom", true), 
			$this->get("word_group", true), $this->get("usage_scope", true), 
			$this->get("tindex", true), $this->get("avindex", true));

		// DEBUG
		//var_dump($query);

		$firstquery = $model->getRestWrappedQuery($query, 1, 0);

		// Fetch a total count of results matching the query and exist session id
		$meta = $model->getAdvancedSearchResultsCount($firstquery);

		// Set a total count of results and session id
		$this->set("count", $meta['count']);
		$_SESSION['advsearch']['count'] = $this->get("count");
		$this->set("sessionid", $meta['sessionid']);
		$_SESSION['advsearch']['existSessionId'] = $this->get("sessionid");
		
		// Check if there's something to show.
		if ($this->get("count") == 0) {

			throw new DICTException(_("AdvancedSearchResults_NoResults_Caption"), _("AdvancedSearchResults_NoResults_Message_Begin") .
				_("AdvancedSearchResults_NoResults_Message_End") . "."
			);
			
		}
		// Make the query
		$fullquery = $model->getRestWrappedQuery($query, 1, $this->get("sessionid"));
		$results = $model->getAdvancedSearchResults($fullquery);
		// DEBUG
		//var_dump($results);
		
		// Set a page title.
		$this->set("title", _("Advanced search results"));
		
		// Set some scripts being used on the page.
		//$this->set("scripts", array("advresults.js"));
		// Sivupalkkien javascript samassa tiedostossa / 7.7.2020 OL
		$this->set("scripts", array("leftbar.js"));
		
		// And finally ask view to render the page.
		$view->header()->navi();
		echo "<div class=\"palstat\">";
		$view->leftbar("AdvSearchLeftBar");
		
		$view->content("AdvancedSearchResults");
		
		echo "</div>";		
		// Render footer.
		$view->footer();
		
	}
	
	// Tarkennetun kwic-haun tulokset
	// UUSI FUNKTIO / 7.7.2026 OL
	public function showKwicSearchResults() {
		
		global $cfg; // Get global configurations / 15.1.2020 OL

		// Set the model to be used.
		$model = $this->setModel("SearchModel");
		// Set the view to be used.
		$view  = $this->setView("SearchView");
		// Set last update date from the config.
		$this->set("last_update", $cfg['KS']['LAST_UPDATE']);

		// Set search-type 17.7.2026 jv
		$_SESSION['advsearchtype'] = "kwicsearch";
		
		// Check wheter a search form has been submitted. If so, save
		// form data into a session variable and redirect user to the
		// results in order to get rid of the POST-data. This way the
		// user won't have to resubmit form data each time when going
		// back from a word article to the search results list.
		if ($_POST) {
			$_SESSION['kwicsearch'] = $_POST;
			header("Location: ./?p=advkwicresults");
		}
		
		// Set a POST-parameters, lisätty sanitointikutsut / 6.10.2026 JV
		$this->set("fulltext", sanitize_input(strtolower($_SESSION['kwicsearch']['fulltext']), 'string'));
		$this->set("fullfrom", sanitize_input($_SESSION['kwicsearch']['fullfrom'], 'string'));
		$this->set("lemma", isset($_SESSION['kwicsearch']['lemma']) ? 1 : 0);
		
		// Check wheter used has filled an essential field.
		if (!$this->get("fulltext")) {
			// Redirect user back to the form and stop execution.
			header("Location: ./?p=advsearchform");
			exit;
		}
	
		// Build up a query to be used with eXist.
		$query = $model->getKwicSearchQuery($this->get("fulltext", true), $this->get("fullfrom", true), 
			$this->get("lemma", true));

		// DEBUG
		//var_dump($query);

		// ensimmäinen kysely, ei sessionid:tä / 17.7.2026 OL
		$firstquery = $model->getKwicRestWrappedQuery($query, $this->get("fullfrom", true), 1, 0);
		// tehdään haku
		$firstresults = $model->getAdvancedSearchResults($firstquery);
		// poimitaan hakutulosten määrä ja sessionid
		$meta = $model->getKwicSearchResultsCount($firstresults);

		// Set a total count of results and session id
		$this->set("count", $meta['count']);
		$_SESSION['advsearch']['count'] = $this->get("count");
		$_SESSION['kwicsearch']['count'] = $this->get("count");
		$this->set("sessionid", $meta['sessionid']);
		$_SESSION['advsearch']['existSessionId'] = $this->get("sessionid");
		$_SESSION['kwicsearch']['existSessionId'] = $this->get("sessionid");
		
		// Check if there's something to show.
		if ($this->get("count") == 0) {

			throw new DICTException(_("AdvancedSearchResults_NoResults_Caption"), 
									_("AdvancedSearchResults_NoResults_Message_Begin") .
									_("AdvancedSearchResults_NoResults_Message_End") . 
				" <a target='_ukk' href='" . $cfg['KS']['UKK_URL'] . "'>" . _("FAQ") . "</a>" . "."
			);
			
		}
		// Make the query
		$kwicquery = $model->getKwicRestWrappedQuery($query, $this->get("fullfrom", true), 1, $this->get("sessionid"));
		$results = $model->getAdvancedSearchResults($kwicquery);
		// DEBUG
		//echo "<pre>";
		//echo htmlspecialchars($results);
		//echo "</pre>";
		
		// Set a page title.
		$this->set("title", _("Advanced search results") . " (KWIC)");
		
		// Sivupalkkien javascript samassa tiedostossa / 7.7.2020 OL
		$this->set("scripts", array("leftbar.js"));
		
		// And finally ask view to render the page.
		$view->header()->navi();
		echo "<div class=\"palstat kwiccontainer\" id=\"kwiccontainer\">";
		$view->leftbar("KwicSearchLeftBar");
		
		$view->content("AdvancedSearchResults");
		
		echo "</div>";		
		// Render footer.
		$view->footer();
		
	}

	/*
		Ajax-funktiot (ajax.php): 
		sivupalkissa näytettävien tietojen haku
	*/

	// UUSI / 18.7.2019 OL
	// Listataan list_id:n perusteella
	public function getAjaxHeadwords () {
		// Set a model to be used.
		$model = $this->setModel("SearchModel");
		// Set a view to be used.
		$view  = $this->setView("SearchView");
		
		// lisätty sanitointikutsu / 6.10.2026 JV
		$id = $this->set("id", isset($_GET['list_id']) ? sanitize_input($_GET['list_id'], 'int', ['default' => 1]) : 1);
		
		$headwords = $model->getHeadwordListById($id);
		$this->set("headwords", $headwords);
		
		$view->datalist("headwords");
	}
	// UUSI / 18.7.2019 OL
	// Listataan prefixin perusteella
	public function getAjaxHeadwordsByPrefix () {
		// Set a model to be used.
		$model = $this->setModel("SearchModel");
		// Set a view to be used.
		$view  = $this->setView("SearchView");
		
		// lisätty sanitointikutsu / 6.10.2026 JV
		$prefix = $this->set("prefix", isset($_GET['prefix']) ? sanitize_input($_GET['prefix'], 'string', ['default' => '']) : '');
		
		$headwords = $model->getHeadwordListByPrefix($prefix);
		$this->set("headwords", $headwords);
		
		$view->datalist("headwords");
	}

	// 27.5.2026 JV
	// Listataan kaikki ensimmäiset hakusanat prefixin perusteella (etusivun aakkoslinkkejä varten)
	public function getAjaxHeadwordsFirstAllByPrefix() {
		// Set a model to be used.
		$model = $this->setModel("SearchModel");
		// Set a view to be used.
		$view  = $this->setView("SearchView");
		
		$headwords = $model->getHeadwordFirstAllByPrefix();
		$this->set("headwords", $headwords);
		
		$view->datalist("headwords");
	}

	// UUSI / 7.11.2019 OL
	// Tehdään pikahaku ja palautetaan tulokset vasempaan sivupalkkiin
	public function getAjaxQuickSearch() {
		// Set a model to be used.
		$model = $this->setModel("SearchModel");
		// Set a view to be used.
		$view  = $this->setView("SearchView");

		// lisätty sanitointikutsut / 6.10.2026 JV
		$keyword = $this->set("keyword", isset($_GET['prefix']) ? sanitize_input($_GET['prefix'], 'string', ['default' => '']) : '');
		$list_id = $this->set("list_id", isset($_GET['list_id']) ? sanitize_input($_GET['list_id'], 'int', ['default' => 1]) : 1);

		$qsresults = $model->getQuickSearchResults($keyword, $list_id);
		$this->set("qsresults", $qsresults);

		$view->datalist("qsresults");
	}

	// Tehdään tarkennettu haku ja palautetaan tulokset vasempaan sivupalkkiin
	public function getAjaxAdvancedSearch() {
		// Set a model to be used.
		$model = $this->setModel("SearchModel");
		// Set a view to be used.
		$view  = $this->setView("SearchView");

		// lisätty sanitointikutsut / 6.10.2026 JV
		$this->set("list_id", isset($_GET['list_id']) ? sanitize_input($_GET['list_id'], 'int', ['default' => 1]) : 1);
		$this->set("sessionid", isset($_GET['existSession']) ? sanitize_input($_GET['existSession'], 'string', ['default' => null]) : null);

		// Get SESSION-parameters
		$this->set("keyword", sanitize_input($_SESSION['advsearch']['keyword'], 'string'));
		$this->set("hwfrom", sanitize_input($_SESSION['advsearch']['hwfrom'], 'string'));
		$this->set("tindex", sanitize_input($_SESSION['advsearch']['tindex'], 'string'));
		$this->set("avindex", sanitize_input($_SESSION['advsearch']['avindex'], 'string'));
		$this->set("word_group", sanitize_input($_SESSION['advsearch']['word_group'], 'string'));
		$this->set("usage_scope", sanitize_input($_SESSION['advsearch']['usage_scope'], 'string'));
		//$this->set("fulltext", strtolower($_SESSION['advsearch']['fulltext']));

		// Build up a query to be used with eXist.
		$query = $model->getAdvancedSearchQuery($this->get("keyword", true), $this->get("hwfrom", true), 
			$this->get("word_group", true), $this->get("usage_scope", true), 
			$this->get("tindex", true), $this->get("avindex", true));
		$fullquery = $model->getRestWrappedQuery($query, $this->get("list_id"), $this->get("sessionid"));
		
		// Execure xquery
		$advresults = $model->getAdvancedSearchResults($fullquery);
		
		$this->set("advresults", $advresults);
		
		// Parsitaan eXistin tuloksesta sivupalkkiin sopivaksi listaksi
		$view->existxml2datalist("advresults");
	}

	// Tehdään tarkennettu kwic-haku ja palautetaan tulokset vasempaan sivupalkkiin
	public function getAjaxKwicSearch() {
		// Set a model to be used.
		$model = $this->setModel("SearchModel");
		// Set a view to be used.
		$view  = $this->setView("SearchView");

		// lisätty sanitointikutsut / 6.10.2026 JV
		$this->set("list_id", isset($_GET['list_id']) ? sanitize_input($_GET['list_id'], 'int', ['default' => 1]) : 1);
		$this->set("sessionid", isset($_GET['existSession']) ? sanitize_input($_GET['existSession'], 'string', ['default' => null]) : null);

		// Get SESSION-parameters, lisätty sanitointikutsut / 6.10.2026 JV
		$this->set("fulltext", sanitize_input($_SESSION['kwicsearch']['fulltext'], 'string'));
		$this->set("fullfrom", sanitize_input($_SESSION['kwicsearch']['fullfrom'], 'string'));
		$this->set("lemma", isset($_SESSION['kwicsearch']['lemma']) ? 1 : 0);

		// Build up a query to be used with eXist.
		$query = $model->getKwicSearchQuery($this->get("fulltext", true), $this->get("fullfrom", true), 
			$this->get("lemma", true));

		// Tarkistetaan onko hakutulos vielä välimuistissa
		$found = $model->checkKwicCache($this->get("sessionid"));
		// Jos tietoja ei löydy välimuistista
		if (!$found) {
			// tehdään koko haku uudestaan ja tallennetaan välimuistiin
			$firstquery = $model->getKwicRestWrappedQuery($query, $this->get("fullfrom", true), 1, 0);
			$firstresults = $model->getAdvancedSearchResults($firstquery);
			// poimitaan hakutulosten määrä ja sessionid
			$meta = $model->getKwicSearchResultsCount($firstresults);
			// Set a total count of results and session id
			$this->set("count", $meta['count']);
			$_SESSION['advsearch']['count'] = $this->get("count");
			$_SESSION['kwicsearch']['count'] = $this->get("count");
			$this->set("sessionid", $meta['sessionid']);
			$_SESSION['advsearch']['existSessionId'] = $this->get("sessionid");
			$_SESSION['kwicsearch']['existSessionId'] = $this->get("sessionid");

		}

		// Tehdään haku välimuistista
		$kwicquery = $model->getKwicRestWrappedQuery($query, $this->get("fullfrom", true), $this->get("list_id"), $this->get("sessionid"));
		$kwicresults = $model->getAdvancedSearchResults($kwicquery);
		
		$this->set("kwicresults", $kwicresults);
		
		// Parsitaan eXistin tuloksesta sivupalkkiin sopivaksi listaksi
		$view->existxml2datalist("kwicresults");
	}

	/*
		Ajax: hakuehdotukset
	*/

	// Pikahaun ehdotukset
	// label=näytettävä merkkijono, value=haussa käytettävä merkkijono
	public function getAjaxResults() {

		// Set a model to be used.
		$model = $this->setModel("SearchModel");
		// Set a view to be used.
		$view  = $this->setView("SearchView");

		$type = $this->set("type", isset($_GET['type']) ? $_GET['type'] : '');
		$query = $this->set("query", isset($_GET['query']) ? $_GET['query'] : '');

		// Send appropriate header to the client.
		header("Content-Type: application/json; charset=utf-8");
		// Output all hits as JSON format.
		//echo json_encode($model->getQuickSearchAjaxResults($query, $type));
		
        $data = $model->getQuickSearchAjaxResults($query, $type);
        $rivit = array();
        foreach ($data as $row) {
			$label = $row['hakusana'];
            if ($row['tyyppi'] == 'V') {
				$label = $label . " →";
            }
            $rivit[] = '{"label":"' . $label .
                     '", "value":"' . $row['hakusana'] . '"}';
        }
        $autocomplete_list = '{"record":[' . "\n" . implode(",\n", $rivit) . ']}';
        echo $autocomplete_list;
		
	}
	
}
?>
