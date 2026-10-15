<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * app/controllers/PageController.php
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * 				Outi Lehtinen ja Jari Vihtari, 2019
*/

class PageController extends Controller {

	public function showMainPage() {
		
		global $cfg;
		
		// Set a model to be used.
		$model = $this->setModel("PageModel");
		
		// Set a view to be used.
		$view = $this->setView("PageView");
		
		// No page title for mainpage.
		$this->set("title", false);
	
		// Set last update date from the config.
		$this->set("last_update", $cfg['KS']['LAST_UPDATE']);
		//$this->set("last_update", $model->getLastUpdated());

        // Hakusanojen lukumäärä tietokannasta
        $word_count = $model->getWordsTotalCount();
        $this->set("word_count", number_format($word_count, 0, '.', ' '));
		
		if ($word->homonyyminro) { 
			$wordhom = $word->hakusana . ":" . $word->homonyyminro; 
		} else { 
			$wordhom = $word->hakusana; 
		} 

		// Save the url and title for the word.
		$this->set("day_word_url", "?p=article&word={$wordhom}&ks_id={$word->ks_id}");
		$this->set("day_word_title", $word->hakusana);
		$this->set("day_word_hom", $word->homonyyminro);
		
		// Set some scripts to be used within a page (ennakoivaa hakua varten)
		//$this->set("scripts", array("ajaxautocomplete.js"));
		$this->set("etusivu", "on");

		// Render a page to the user.
		$view->header()->navi()->content("MainPage")->footer();
		
	}

	public function showBackground() {
		
		global $cfg;

		// Set a view to be used.
		$view = $this->setView("PageView");

		// Tarvitaan aakkosvalin tulostamista varten / 5.2.2020 OL
		// Set a model to be used.
		$model = $this->setModel("PageModel");
		// Get and save the first word found from database.
		$this->set("first_word", $model->getFirstWordArticle()->hakusana);
		// Get and save the last word found from database.
		$this->set("last_word", $model->getLastWordArticle()->hakusana);
		// Set last update date from the config.
		$this->set("last_update", $cfg['KS']['LAST_UPDATE']);
		
		// Set a page title.
		$this->set("title", _("Background_HTML_Title"));
		
		// Set some scripts to be used within a page.
		$this->set("scripts", array("leftbar.js"));
		
		// Save (possible) subpage id.
		$this->set("subpage", isset($_GET['sp']) ? intval($_GET['sp']) : 1);
		
		// Select page to be rendered.
		switch($this->get("subpage")) {
			case 2:
				$page = "Background2";
				break;
				
			case 3:
				$page = "Background3";
				break;
			
			case 4:
				$page = "Background4";
				break;
				
			default:
				$page = "Background";
		}

		// Render a page to the user.
		$view->header()->navi();
		echo "<div class=\"palstat\">";
		$view->leftbar()->content($page);
		echo "</div>";
		$view->footer();
		
	}

	public function showHelp() {
		
		global $cfg;

		// Set a view to be used.
		$view = $this->setView("PageView");

		// Tarvitaan aakkosvalin tulostamista varten / 5.2.2020 OL
		// Set a model to be used.
		$model = $this->setModel("PageModel");
		// Get and save the first word found from database.
		$this->set("first_word", $model->getFirstWordArticle()->hakusana);
		// Get and save the last word found from database.
		$this->set("last_word", $model->getLastWordArticle()->hakusana);
		// Set last update date from the config.
		$this->set("last_update", $cfg['KS']['LAST_UPDATE']);
        // Get and save the total count of Real Words found from database.
        $word_count = $model->getWordsTotalCount();
        $this->set("word_count", number_format($word_count, 0, '.', ' '));
		
		// Set some scripts to be used within a page.
		$this->set("scripts", array("leftbar.js"));
		
		// Save (possible) subpage id.
		$this->set("subpage", isset($_GET['sp']) ? intval($_GET['sp']) : 1);
		
		// Select page to be rendered.
		switch($this->get("subpage")) {
			case 2:
				$page = "Instructions2";
				$this->set("title", _("ReadInstructions_HTML_Title"));
				break;
				
			default:
				$page = "Instructions";
				$this->set("title", _("SearchInstructions_HTML_Title"));
		}

		// Render a page to the user.
		$view->header()->navi();
		echo "<div class=\"palstat\">";
		$view->leftbar()->content($page);
		echo "</div>";
		$view->footer();
		
	}

	public function showQuickHelp() {
		
		global $cfg;

		// Set a view to be used.
		$view = $this->setView("PageView");
		
		// Set last update date from the config.
		$this->set("last_update", $cfg['KS']['LAST_UPDATE']);

		// Set a page title.
		$this->set("title", _("QuickHelp_HTML_Title"));
		
		// Use no scripts on the page.
		$this->set("scripts", array());

		// Render a page to the user.
		$view->header("PopupHeader")->content("Instructions")->footer();
		
	}

	public function showAbbreviations() {
		
		global $cfg;

		// Set a view to be used.
		$view = $this->setView("PageView");

		// Tarvitaan aakkosvalin tulostamista varten / 5.2.2020 OL
		// Set a model to be used.
		$model = $this->setModel("PageModel");
		// Get and save the first word found from database.
		$this->set("first_word", $model->getFirstWordArticle()->hakusana);
		// Get and save the last word found from database.
		$this->set("last_word", $model->getLastWordArticle()->hakusana);
		// Set last update date from the config.
		$this->set("last_update", $cfg['KS']['LAST_UPDATE']);
		
		// Set a page title.
		$this->set("title", _("Abbreviations_HTML_Title"));
		
		// Set some scripts to be used on a page.
		$this->set("scripts", array("leftbar.js"));
		
		// Save (possible) subpage id.
		$this->set("subpage", isset($_GET['sp']) ? intval($_GET['sp']) : 1);
		
		// Select page to be rendered.
		switch($this->get("subpage")) {
			case 2:
				$page = "Abbreviations2";
				break;
				
			case 3:
				$page = "Abbreviations3";
				break;
				
			case 4:
				$page = "Abbreviations4";
				break;
				
			default:
				$page = "Abbreviations";
		}
		
		// Render a page to the user.
		$view->header()->navi();
		echo "<div class=\"palstat\">";
		$view->leftbar()->content($page);
		echo "</div>";
		$view->footer();
		
	}

	public function showReferences() {
		
		global $cfg;

		// Set a view to be used.
		$view = $this->setView("PageView");

		// Tarvitaan aakkosvalin tulostamista varten / 5.2.2020 OL
		// Set a model to be used.
		$model = $this->setModel("PageModel");
		// Get and save the first word found from database.
		$this->set("first_word", $model->getFirstWordArticle()->hakusana);
		// Get and save the last word found from database.
		$this->set("last_word", $model->getLastWordArticle()->hakusana);
		// Set last update date from the config.
		$this->set("last_update", $cfg['KS']['LAST_UPDATE']);
		
		// Set a page title.
		$view->set("title", _("References_HTML_Title"));
		
		// Set some scripts to be used on a page.
		$this->set("scripts", array("leftbar.js"));
		
		// Render a page to the user.
		$view->header()->navi();
		echo "<div class=\"palstat\">";
		$view->leftbar()->content("References");
		echo "</div>";
		$view->footer();
		
	}


	
	public function showException() {
		
		// Used in DICTException.
		// Needed for getting access to MVC-structure.
		return $this;
		
	}
	
	

	public function getArticleOfDay() {

		// Set the model to be used.
		$model = $this->setModel("ArticleModel");

		return $model->getArticleOfDay();

	}
	
}

?>
