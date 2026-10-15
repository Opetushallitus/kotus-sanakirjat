<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * inc/Exceptions.php
 *
 * Exceptions for various purposes.
 * To be thrown/catched in try-catch block in case of an error.
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * 				Outi Lehtinen ja Jari Vihtari, 2019
*/

final class QuerySQLException extends PDOException {

	// Class constructor.
	// Will be executed when a class is initialized.
	public function __construct($message) {

		global $cfg;

		// Initialize PageController.
		// Needed for setting the view and to render the whole page.
		$ctrl = new PageController("showException");
		
		// aakkosv�lin tiedot / 5.2.2020 OL
		// Set a model to be used.
		$model = $ctrl->setModel("PageModel");
		// Set last update date from the config.
		$ctrl->set("last_update", $cfg['KS']['LAST_UPDATE']);

		// Set a view to be used.
		$view = $ctrl->setView("PageView");

		// Save the error message.
		$view->set("message", $message, false);
		
		// Set some scripts to be used within a page.
		$view->set("scripts", array("leftbar.js"));
		
		// Render a page to the user.
		$view->header()->navi();
		echo "<div class=\"palstat\">";
		$view->leftbar()->content("SQLException");

		echo "</div>";
		$view->footer();
		
		// Stop execution.
		die();
		
	}

}


final class QueryXMLException extends SoapFault {

	// Class constructor.
	// Will be executed when a class is initialized.
	public function __construct($message) {
		
		global $cfg;

		// Initialize PageController.
		// Needed for setting the view and to render the whole page.
		$ctrl = new PageController("showException");
		
		// aakkosv�lin tiedot / 5.2.2020 OL
		// Set a model to be used.
		$model = $ctrl->setModel("PageModel");
		// Set last update date from the config.
		$ctrl->set("last_update", $cfg['KS']['LAST_UPDATE']);

		// Set a view to be used.
		$view = $ctrl->setView("PageView");
		
		// Save the error message.
		$view->set("message", $message, false);
		
		// Set some scripts to be used within a page.
		$view->set("scripts", array("leftbar.js"));
		
		// Render a page to the user.
		$view->header()->navi();
		echo "<div class=\"palstat\">";
		$view->leftbar()->content("XMLException");
		echo "</div>";
		$view->footer();

		// Stop execution.
		die();
		
	}

}


class DICTException extends Exception {

	// Class constructor.
	// Will be executed when a class is initialized.
	public function __construct($caption, $message) {
		
		global $cfg;

		// Initialize PageController.
		// Needed for setting the view and to render the whole page.
		$ctrl  = new PageController("showException");
		
		// Set a view to be used.
		$view  = $ctrl->setView("PageView");
		
		// aakkosv�lin tiedot / 5.2.2020 OL
		// Set a model to be used.
		$model = $ctrl->setModel("PageModel");
		// Set last update date from the config.
		$ctrl->set("last_update", $cfg['KS']['LAST_UPDATE']);

		// Check wheter a custom caption has been given.
		if (!$caption) {
			
			// If not, use the default caption.
			$caption = _("Unexpected error");
			
		}

		// Save a page title.
		$view->set("title", $caption);
		
		// Save a page caption.
		$view->set("caption", $caption);
		
		// Save the error message.
		$view->set("message", $message, false);
		
		// Set some scripts to be used within a page.
		$view->set("scripts", array("leftbar.js"));
		
		// Render a page to the user.
		$view->header()->navi();
		echo "<div class=\"palstat\">";
		$view->leftbar()->content("DICTException");
		echo "</div>";
		$view->footer();
		
		// Stop execution.
		die();
		
	}

}


class SysException extends Exception {

	// Class constructor.
	// Will be executed when a class is initialized.
	public function __construct($message) {
		
		global $cfg;

		// Initialize PageController.
		// Needed for setting the view and to render the whole page.
		$ctrl = new PageController("showException");
		
		// aakkosv�lin tiedot / 5.2.2020 OL
		// Set a model to be used.
		$model = $ctrl->setModel("PageModel");
		// Set last update date from the config.
		$ctrl->set("last_update", $cfg['KS']['LAST_UPDATE']);

		// Set a view to be used.
		$view = $ctrl->setView("PageView");
		
		// Save a page title.
		$view->set("title", _("Unexpected critical error"));
		
		// Set a message to be shown to the user.
		$view->set("message", $message, false);

		// Set some scripts to be used within a page.
		$view->set("scripts", array("leftbar.js"));
		
		// Render a page to the user.
		$view->header()->navi();
		echo "<div class=\"palstat\">";
		$view->leftbar()->content("SYSException");
		echo "</div>";
		$view->footer();
		
		// Stop execution.
		die();

	}

}

?>
