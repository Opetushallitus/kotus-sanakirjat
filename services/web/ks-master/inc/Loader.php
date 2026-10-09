<?php
if (!constant("IN_KS")) exit("General error, exiting.");

/**
 * inc/Loader.php
 *
 * Autoloader for configs, includers and application specific files.
 * Includes all essential files from pre-known directories.
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author:		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
*/

try {
	// All configuration files need to be included first.
	// Includes all matches found with *.php pattern if the file in question are not yet included.
	foreach(glob("{cfg/*.php,inc/*.php}", GLOB_BRACE) as $file) {
		include_once $file;
	}

	// Before including all MVC-related files, we have to include all
	// abstract base classes so the child classes can inherit them properly.
	// PHP will result in a fatal error if the parent class doesn't exist.
	include_once $cfg['KS']['BASE_PATH'] . "/app/models/Model.php";
	include_once $cfg['KS']['BASE_PATH'] . "/app/controllers/Controller.php";
	include_once $cfg['KS']['BASE_PATH'] . "/app/views/View.php";

	// Then we can include all MVC-related files.
	// Includes all matches found with *.php pattern if the file in question are not yet included.
	foreach(glob("{app/models/*.php,app/controllers/*.php,app/views/*.php}", GLOB_BRACE) as $file) {
		include_once $file;
	}
	
} catch (Exception $e) {
	echo $e->getMessage();
}

?>
