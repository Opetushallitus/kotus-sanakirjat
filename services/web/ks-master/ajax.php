<?php

/**
 * ajax.php
 *
 * Main handler between the user and the application.
 *
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * Copyright: 	Institute for the Languages of Finland
 *				Outi Lehtinen ja Jari Vihtari, 2019
*/

// Initialize the application.
include_once "inc/DICT_init.php";

switch ($_GET['m']) {

	case "hakusanat":
		new SearchController("getAjaxHeadwords");
		break;

	case "hakusanat-prefix":
		new SearchController("getAjaxHeadwordsByPrefix");
		break;
	
	case "hakusanat-prefix-first":
		new SearchController("getAjaxHeadwordsFirstByPrefix");
		break;
	
	case "hakusanat-prefix-first-all":
		new SearchController("getAjaxHeadwordsFirstAllByPrefix");
		break;
	
	case "qs-ajax-results":
		new SearchController("getAjaxResults");
		break;

	case "qs-results":
		new SearchController("getAjaxQuickSearch");
		break;

	case "as-results":
		new SearchController("getAjaxAdvancedSearch");
		break;

	case "kwic-results":
		new SearchController("getAjaxKwicSearch");
		break;

}


?>
