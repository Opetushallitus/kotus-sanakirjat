<?php

/**
 * inc/DICT_init.php
 *
 * Initialization for application.
 * Execution must happen at first, even before the loader.
 * Take a look at index.php to see usage in practise.
 *
 * Copyright:	Institute for the Languages of Finland
 * Author:		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
*/

// Send few custom headers to client.
header("Content-Type: text/html; charset=utf8");

// Give a name to session and start it.
session_name("KS");
session_start();

// the application to production. User don't need to know about the errors and any other application
// It's a good idea to show all errors at the development stage, but let's hide them when releasing
// details, for example the path to this directory. As instead, there's a few Exception classes for
// taking in all custom exceptions in case of error. That way we can show a clean and pretty
// view to user with undestandable error details.

//error_reporting(E_ALL ^ E_NOTICE);
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

// Set a default timezone.
date_default_timezone_set('Europe/Helsinki');

// Improve security by setting up an internal flag, what will be checked in all critical files.
// If the flag check don't return true, the application will quit instantly. This ensures that
// no-one can run a single file without running the whole application, as it should go.

define('IN_KS', '1');

// Security trick which removes all magic quotes from user input. It's very important,
// because it prevents usage of injections. PDO will check and clean incoming MySQL data,
// so there's no need to check it separately. This feature has been deprecated as of PHP
// 5.3.0 so it's good to escape the data at runtime.

/*if (get_magic_quotes_gpc()) {
	function stripslashes_gpc(&$value) {
		$value = stripslashes($value);
	}
	array_walk_recursive($_GET, 'stripslashes_gpc');
	array_walk_recursive($_POST, 'stripslashes_gpc');
	array_walk_recursive($_COOKIE, 'stripslashes_gpc');
	array_walk_recursive($_REQUEST, 'stripslashes_gpc');
}*/

// Last step, include and execute the loader.
include_once "Loader.php";


?>
