<?php

/**
 * index.php
 *
 * Main handler between the user and the application.
 * 
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2012
 * 				Outi Lehtinen ja Jari Vihtari, 2019
*/


// Initialize the application.
include_once "inc/DICT_init.php";

$uriparts = explode("/", $_SERVER["REQUEST_URI"]);
$hakusana = isset($uriparts[2]) && !str_starts_with($uriparts[2], '?') ? $uriparts[2] : '';

// Sivu (?p) vai pikaosoite
if (isset($_GET['p'])) {
	$page = (!empty($_GET['p'])) ? $_GET['p'] : "main";	
} else if (!empty($hakusana)) {
	$page = "shortaddress";
	//$hakusana = $_GET['h'];
} else {
	$page = "main";
}
//$page = (isset($_GET['p'])) ? $_GET['p'] : "main";

switch ($page) {

	// HAKUSANALUETTELO
	case "headwords":
		if (isset($_GET['prefix'])) {
			new ArticleController("showWordArticle");
		}
		else {
			new SearchController("showHeadwordList");
		}
		break;

	// PIKAHAKU: tulokset
	case "searchresults":
		new SearchController("showQuickSearchResults");
		break;

	// TARKENNETTU HAKU: lomake
	case "advsearchform":
		new SearchController("showAdvancedSearchForm");
		break;
	// TARKENNETTU HAKU: tulokset
	case "advsearchresults":
		new SearchController("showAdvancedSearchResults");
		break;
	// TARKENNETTU HAKU: kwic-tulokset
	case "advkwicresults":
		new SearchController("showKwicSearchResults");
		break;
	
	// ARTIKKELIN NÄYTTÄMINEN
	case "article":
		new ArticleController("showWordArticle");
		break;
	// pikahaun tuloksissa
	case "qs-article":
		new ArticleController("showSearchedWordArticle");
		break;
	// tarkennetun haun tuloksissa
	case "as-article":
		new ArticleController("showAdvSearchedWordArticle");
		break;
	// tarkennetun kwic-haun tuloksissa
	case "kwic-article":
		new ArticleController("showKwicSearchedWordArticle");
		break;		

	// STAATTISET SIVUT
	case "background":
		new PageController("showBackground");
		break;

	case "help":
		new PageController("showHelp");
		break;

	case "quickhelp":
		new PageController("showQuickHelp");
		break;

	case "abbreviations":
		new PageController("showAbbreviations");
		break;

	// kielen vaihto
	case "chlang":
		changeCurrentLocale();
		break;
	
	// lyhytosoite hakusanalla
	case "shortaddress":
		// käsitellään samaan tapaan kuin pikahaku
		$_POST['keyword'] = $hakusana;
		new SearchController("showQuickSearchResults");
		break;

	// ETUSIVU
	case "main":
	default:
		new PageController("showMainPage");
		break;

}

?>

