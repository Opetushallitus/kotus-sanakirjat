<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * app/controllers/ArticleController.php
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * 				Outi Lehtinen ja Jari Vihtari, 2019
*/

class ArticleController extends Controller {

	// Artikkelin näyttäminen selatessa
	public function showWordArticle() {
		
		global $cfg;

		// Set the model to be used.
		$model = $this->setModel("ArticleModel");
		// Set the view to be used.
		$view  = $this->setView("ArticleView");

		// Set last update date from the config.
		$this->set("last_update", $cfg['KS']['LAST_UPDATE']);

		// Save a GET-parameter 'word', lisätty sanitaatiokutsu / 6.10.2026 JV
		$this->set("word", isset($_GET['word']) ? sanitize_input($_GET['word'], 'string', ['default' => '']) : '');
		
		// Save a GET-parameter for dictionary id, lisätty sanitaatiokutsu / 6.10.2026 JV
		$this->set("ks_id", isset($_GET['ks_id']) ? sanitize_input($_GET['ks_id'], 'ks-id', ['default' => 'id-puuttuu']) : 'id-puuttuu');

		// Save a GET-parameter for first headword in the list / 19.7.2019 OL
		if (isset($_GET['list_id'])) {
			// lisätty sanitaatiokutsu / 6.10.2026 JV
			$this->set("list_id", sanitize_input($_GET['list_id'], 'int'));
		} else {
			$listid = $model->getHeadwordListIndexByWord($this->get("word"));
			$this->set('list_id', $listid);
		}
		
		// Näkymävalinta
		$this->set("viewtype", isset($_GET['view']) ? sanitize_input($_GET['view'], 'string', ['default' => 'full']) : 'full');
		// näkymän nimi titleä varten / 11.6.2020 OL
		if ($this->get("viewtype") == "inflection") {
			$viewtitle = _("Article_Viewtype_Inflection");
		} else if ($this->get("viewtype") == "examples") {
			$viewtitle = _("Article_Viewtype_Examples");
		} else {
			$viewtitle = _("Article_Viewtype_Full");
		}

		// Set some scripts to be used within a page.
		$this->set("scripts", array("leftbar.js"));
		
		// Hae artikkeli XML-tietokannasta ja palauta HTML-muodossa
		$data = $model->getWordArticle($this->get("ks_id"), '', $this->get("viewtype"));

		// haetaan artikkelin hakusana
		$hw = $model->getArticleHeadword($this->get("ks_id"));
		$hakusana = ($hw) ? $hw : strip_tags($this->get("word"));
		//muodostetaan title
		$this->set("title",  $hakusana . " - " . $viewtitle);

		// Rakennetaan polut artikkelilinkin pohjaksi
		$this->set("articlepath", $model->getArticlePath($this->get("ks_id"), $this->get("word")));
		$this->set("permalink", $cfg['KS']['KS_PATH'] . $hakusana);

		// Save article's data
		$this->set("article", $data, false);
		
		// Render a page to the user.
		$view->header()->navi();
		echo "<div class=\"palstat\">";
		// vasen palkki
		$view->leftbar();
		// oikeassa palkissa artikkeli
		$view->content("WordArticle");
		echo "</div>";
		$view->footer();
		
	}

	// artikkeli pikahaun tuloksissa
	public function showSearchedWordArticle() {
		
		global $cfg;

		// Set the models to be used.
		$model = $this->setModel("ArticleModel");
		$smodel = $this->setModel("SearchModel");

		// Set the view to be used.
		$view  = $this->setView("ArticleView");

		$this->set("keyword", isset($_GET['keyword']) ? sanitize_input($_GET['keyword'], 'string', ['default' => '']) : '');

		// Set last update date from the config.
		$this->set("last_update", $cfg['KS']['LAST_UPDATE']);

		// Hakutulosten lkm
		$count = $smodel->getQuickSearchResultsCount($this->get("keyword"));
		// Set a total count of results.
		$this->set("count", $count);

		// Save a GET-parameter 'word', lisätty sanitaatiokutsu / 6.10.2026 JV
		$this->set("word", isset($_GET['word']) ? sanitize_input($_GET['word'], 'string', ['default' => '']) : '');
		
		// Save a GET-parameter for dictionary id, lisätty sanitaatiokutsu / 6.10.2026 JV
		$this->set("ks_id", isset($_GET['ks_id']) ? sanitize_input($_GET['ks_id'], 'ks-id', ['default' => 'id-puuttuu']) : 'id-puuttuu');

		// Save a GET-parameter for first headword in the list / 19.7.2019 OL
		// lisätty sanitaatiokutsu / 6.10.2026 JV
		$this->set("list_id", isset($_GET['list_id']) ? sanitize_input($_GET['list_id'], 'int', ['default' => 1]) : 1);
		
		// Save a GET-parameter 'view', lisätty sanitaatiokutsu / 6.10.2026 JV
		// This controls the way how user will see an article.
		$this->set("viewtype", isset($_GET['view']) ? sanitize_input($_GET['view'], 'string', ['default' => 'full']) : 'full');
		// näkymän nimi titleä varten / 11.6.2020 OL
		if ($this->get("viewtype") == "inflection") {
			$viewtitle = _("Article_Viewtype_Inflection");
		} else if ($this->get("viewtype") == "examples") {
			$viewtitle = _("Article_Viewtype_Examples");
		} else {
			$viewtitle = _("Article_Viewtype_Full");
		}
		
		// Set some scripts to be used within a page.
		//$this->set("scripts", array("results.js", "showhiddenlist.js"));
		// Sivupalkkien javascript samassa tiedostossa / 7.7.2020 OL
		$this->set("scripts", array("leftbar.js"));
		
		// Get a word article from model.
		// OL 13.2.2012
		$data = $model->getWordArticle($this->get("ks_id"), '', $this->get("viewtype"));

		// haetaan artikkelin hakusana
		$hw = $model->getArticleHeadword($this->get("ks_id"));
		$hakusana = ($hw) ? $hw : strip_tags($this->get("word"));
		//muodostetaan title
		$this->set("title",  $hakusana . " - " . $viewtitle);

		// Rakennetaan polut artikkelilinkin pohjaksi
		$this->set("articlepath", $model->getArticlePath($this->get("ks_id"), $this->get("word")));
		$this->set("permalink", $cfg['KS']['KS_PATH'] . $hakusana);

		// Save article's data.
		$this->set("article", $data, false);
		
		// Render a page to the user.
		$view->header()->navi();
		echo "<div class=\"palstat\">";
		// vasen hakupalkki
		$view->leftbar("SearchLeftBar");
		// oikeassa palkissa artikkeli
		$view->content("WordArticle");
		echo "</div>";
		$view->footer();
		
	}

	// artikkeli tarkennetun haun tuloksissa
	//
	// UUSI osumien korostustapa / 27.4.2021 OL
	//
	public function showAdvSearchedWordArticle() {
		
		global $cfg;
		$model = $this->setModel("ArticleModel");
		$view  = $this->setView("ArticleView");
		$this->set("last_update", $cfg['KS']['LAST_UPDATE']);
		$this->set("count", $_SESSION['advsearch']['count']);
		$this->set("sessionid", $_SESSION['advsearch']['existSessionId']);

		// lisätty sanitaatiokutsut / 6.10.2026 JV
		$this->set("word", isset($_GET['word']) ? sanitize_input($_GET['word'], 'string', ['default' => '']) : '');
		$this->set("ks_id", isset($_GET['ks_id']) ? sanitize_input($_GET['ks_id'], 'ks-id', ['default' => 'id-puuttuu']) : 'id-puuttuu');
		$this->set("list_id", isset($_GET['list_id']) ? sanitize_input($_GET['list_id'], 'int', ['default' => 1]) : 1);
		$this->set("viewtype", isset($_GET['view']) ? sanitize_input($_GET['view'], 'string', ['default' => 'full']) : 'full');
		// näkymän nimi titleä varten / 11.6.2020 OL
		if ($this->get("viewtype") == "inflection") {
			$viewtitle = _("Article_Viewtype_Inflection");
		} else if ($this->get("viewtype") == "examples") {
			$viewtitle = _("Article_Viewtype_Examples");
		} else {
			$viewtitle = _("Article_Viewtype_Full");
		}
		
		$this->set("scripts", array("leftbar.js"));		
		$this->set("advresults", "false");

		$data = $model->getWordArticle($this->get("ks_id"), '', $this->get("viewtype"));

		// haetaan artikkelin hakusana
		$hw = $model->getArticleHeadword($this->get("ks_id"));
		$hakusana = ($hw) ? $hw : strip_tags($this->get("word"));
		//muodostetaan title
		$this->set("title",  $hakusana . " - " . $viewtitle);

		// Rakennetaan polut artikkelilinkin pohjaksi
		$this->set("articlepath", $model->getArticlePath($this->get("ks_id"), $this->get("word")));
		$this->set("permalink", $cfg['KS']['KS_PATH'] . $hakusana);

		$this->set("article", $data, false);
		
		$view->header()->navi();
		echo "<div class=\"palstat\">";
		$view->leftbar("AdvSearchLeftBar");
		$view->content("WordArticle");
		echo "</div>";
		$view->footer();
		
	}

	// artikkeli tarkennetun kwic-haun tuloksissa
	// UUSI FUNKTIO / 9.7.2026 OL
	//
	public function showKwicSearchedWordArticle() {
		
		global $cfg;
		$model = $this->setModel("ArticleModel");
		$view  = $this->setView("ArticleView");
		$this->set("last_update", $cfg['KS']['LAST_UPDATE']);
		$this->set("count", isset($_SESSION['advsearch']['count']) ? $_SESSION['advsearch']['count'] : null); // isset-lisäys 18.9.2026 JV
		$this->set("sessionid", isset($_SESSION['advsearch']['existSessionId']) ? $_SESSION['advsearch']['existSessionId'] : null); // isset-lisäys 18.9.2026 JV

		// lisätty sanitaatiokutsut / 6.10.2026 JV
		$this->set("word", isset($_GET['word']) ? sanitize_input($_GET['word'], 'string', ['default' => '']) : '');
		$this->set("ks_id", isset($_GET['ks_id']) ? sanitize_input($_GET['ks_id'], 'ks-id', ['default' => 'id-puuttuu']) : 'id-puuttuu');
		$this->set("list_id", isset($_GET['list_id']) ? sanitize_input($_GET['list_id'], 'int') : 1);
		$this->set("viewtype", isset($_GET['view']) ? sanitize_input($_GET['view'], 'string', ['default' => 'full']) : 'full');
		$this->set("osumanro", isset($_GET['mpos']) ? sanitize_input($_GET['mpos'], 'int', ['default' => 1]) : 1);
		// näkymän nimi titleä varten / 11.6.2020 OL
		if ($this->get("viewtype") == "inflection") {
			$viewtitle = _("Article_Viewtype_Inflection");
		} else if ($this->get("viewtype") == "examples") {
			$viewtitle = _("Article_Viewtype_Examples");
		} else {
			$viewtitle = _("Article_Viewtype_Full");
		}
		
		$this->set("scripts", array("leftbar.js"));		
		$this->set("kwicresults", "false");

		$data = $model->getKwicWordArticle($this->get("ks_id"), $this->get("osumanro"), $this->get("viewtype"));

		// haetaan artikkelin hakusana
		$hw = $model->getArticleHeadword($this->get("ks_id"));
		$hakusana = ($hw) ? $hw : strip_tags($this->get("word"));
		//muodostetaan title
		$this->set("title",  $hakusana . " - " . $viewtitle);

		// Rakennetaan polut artikkelilinkin pohjaksi
		$this->set("articlepath", $model->getArticlePath($this->get("ks_id"), $this->get("word")));
		$this->set("permalink", $cfg['KS']['KS_PATH'] . $hakusana);

		$this->set("article", $data, false);
		
		$view->header()->navi();
		echo "<div class=\"palstat kwiccontainer\" id=\"kwiccontainer\">";
		$view->leftbar("KwicSearchLeftBar");
		$view->content("WordArticle");
		echo "</div>";
		$view->footer();
		
	}


}

?>
