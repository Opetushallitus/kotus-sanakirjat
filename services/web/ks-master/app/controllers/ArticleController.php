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

		// Get and save the first word found from database.
		$this->set("first_word", $model->getFirstWordArticle()->hakusana);		
		// Get and save the last word found from database.
		$this->set("last_word", $model->getLastWordArticle()->hakusana);
		// Set last update date from the config.
		$this->set("last_update", $cfg['KS']['LAST_UPDATE']);

		// Save a GET-parameter 'word'.
		$this->set("word", isset($_GET['word']) ? $_GET['word'] : '');
		
		// Save a GET-parameter 'mtype' - OL 13.2.2012
		$this->set("mtype", isset($_GET['type']) ? $_GET['type'] : null);

		// Save a GET-parameter for dictionary id.
		$this->set("ks_id", isset($_GET['ks_id']) ? $_GET['ks_id'] : 'id-puuttuu');

		// Save a GET-parameter for first headword in the list / 19.7.2019 OL
		//$this->set("list_id", $_GET['list_id']);
		if (isset($_GET['list_id'])) {
			$this->set("list_id", $_GET['list_id']);
		} else {
			$listid = $model->getHeadwordListIndexByWord($this->get("word"));
			$this->set('list_id', $listid);
		}
		
		// Save a GET-parameter 'view'.
		// This controls the way how user will see an article.
		$this->set("viewtype", isset($_GET['view']) ? $_GET['view'] : 'full');
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
		
		// Get a word article from model.
		// with information of dialect form for highlighting
		// OL 13.2.2012
		if ($this->get("mtype") == "m") {
		  $data = $model->getWordArticle($this->get("ks_id"), $this->get("word"), $this->get("viewtype"));
		} // or not
		else {
		  $data = $model->getWordArticle($this->get("ks_id"), '', $this->get("viewtype"));
		}

		// Grab title from MySQL, else from GET-param word.
		// lisätään titleen näkymän nimi / 11.6.2020 OL
		$temp = $model->getArticleById($this->get("ks_id"));
		$atitle = ($temp) ? $temp->hakusana : strip_tags($this->get("word"));
		$this->set("title",  $atitle . " - " . $viewtitle);
		// tallennetaan hakusana palautetta varten / 30.4.2026 OL
		$this->set("hakusana", $atitle);
		$temp = null;

		// Rakennetaan polku pysyvän artikkelilinkin pohjaksi / 31.12.2019 OL
		$this->set("permalink", $model->getArticlePath($this->get("ks_id"), $this->get("word")));

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

		$this->set("keyword", isset($_GET['keyword']) ? $_GET['keyword'] : '');

		// Get and save the first word found from database.
		$this->set("first_word", $model->getFirstWordArticle()->hakusana);		
		// Get and save the last word found from database.
		$this->set("last_word", $model->getLastWordArticle()->hakusana);
		// Set last update date from the config.
		$this->set("last_update", $cfg['KS']['LAST_UPDATE']);

		// Hakutulosten lkm
		$count = $smodel->getQuickSearchResultsCount($this->get("keyword"));
		// Set a total count of results.
		$this->set("count", $count);

		// Save a GET-parameter 'word'.
		$this->set("word", isset($_GET['word']) ? $_GET['word'] : '');
		
		// Save a GET-parameter 'mtype' - OL 13.2.2012
		$this->set("mtype", isset($_GET['type']) ? $_GET['type'] : null);

		// Save a GET-parameter for dictionary id.
		$this->set("ks_id", isset($_GET['ks_id']) ? $_GET['ks_id'] : 'id-puuttuu');

		// Save a GET-parameter for first headword in the list / 19.7.2019 OL
		$this->set("list_id", isset($_GET['list_id']) ? $_GET['list_id'] : 1);
		
		// Save a GET-parameter 'view'.
		// This controls the way how user will see an article.
		$this->set("viewtype", isset($_GET['view']) ? $_GET['view'] : 'full');
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
		// with information of dialect form for highlighting
		// OL 13.2.2012
		if ($this->get("mtype") == "m") {
		  $data = $model->getWordArticle($this->get("ks_id"), $this->get("word"), $this->get("viewtype"));
		} // or not
		else {
		  $data = $model->getWordArticle($this->get("ks_id"), '', $this->get("viewtype"));
		}

		// Grab title from MySQL, else from GET-param word.
		// lisätään titleen näkymän nimi / 9.4.2021 OL
		$temp = $model->getArticleById($this->get("ks_id"));
		$atitle = ($temp) ? $temp->hakusana : strip_tags($this->get("word"));
		$this->set("title",  $atitle . " - " . $viewtitle);
		$temp = null;

		// Rakennetaan polku pysyvän artikkelilinkin pohjaksi / 31.12.2019 OL
		$this->set("permalink", $model->getArticlePath($this->get("ks_id"), $this->get("word")));

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

		$this->set("word", isset($_GET['word']) ? $_GET['word'] : '');
		$this->set("mtype", isset($_GET['type']) ? $_GET['type'] : null);
		$this->set("ks_id", isset($_GET['ks_id']) ? $_GET['ks_id'] : 'id-puuttuu');
		$this->set("list_id", isset($_GET['list_id']) ? $_GET['list_id'] : 1);
		$this->set("viewtype", isset($_GET['view']) ? $_GET['view'] : 'full');
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

		if ($this->get("mtype") == "m") {
		  $data = $model->getWordArticle($this->get("ks_id"), $this->get("word"), $this->get("viewtype"));
		} // or not
		else {
		  $data = $model->getWordArticle($this->get("ks_id"), '', $this->get("viewtype"));
		}

		// lisätään titleen näkymän nimi / 9.4.2021 OL
		$temp = $model->getArticleById($this->get("ks_id"));
		$atitle = ($temp) ? $temp->hakusana : strip_tags($this->get("word"));
		$this->set("title",  $atitle . " - " . $viewtitle);
		$temp = null;

		$this->set("permalink", $model->getArticlePath($this->get("ks_id"), $this->get("word")));

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

		$this->set("word", isset($_GET['word']) ? $_GET['word'] : '');
		$this->set("ks_id", isset($_GET['ks_id']) ? $_GET['ks_id'] : 'id-puuttuu');
		$this->set("list_id", isset($_GET['list_id']) ? $_GET['list_id'] : 1);
		$this->set("viewtype", isset($_GET['view']) ? $_GET['view'] : 'full');
		$this->set("osumanro", isset($_GET['mpos']) ? $_GET['mpos'] : 1);
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

		// lisätään titleen näkymän nimi / 9.4.2021 OL
		$temp = $model->getArticleById($this->get("ks_id"));
		$atitle = ($temp) ? $temp->hakusana : strip_tags($this->get("word"));
		$this->set("title",  $atitle . " - " . $viewtitle);
		$temp = null;

		$this->set("permalink", $model->getArticlePath($this->get("ks_id"), $this->get("word")));

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
