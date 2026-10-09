<?php

/**
 * inc/Functions.php
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author:		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
*/

function changeCurrentLocale() {
	global $cfg;

	// Fetch the new locale code.
	$lcl_code = $_GET['l'];

	// Checks if the requested new locale exists in enabled locales array.
	if (array_key_exists($lcl_code, $cfg['LCL']['ENABLED'])) {

		// Store the new language code into a session.
		$_SESSION['KS_lang'] = $lcl_code;

		// Set the new locale and assign some environment variables.
		textdomain($lcl_code);
		putenv("LANG=$lcl_code");
		putenv("LC_MESSAGES=$lcl_code");
		setlocale(LC_MESSAGES, $lcl_code);

		// Redirect user to the main page.
		header("Location: ./");

	} else {

		// If the given new language code don't exist in enabled locales
		// array, print an error message and terminate the script.
		die(_("Requested locale could not found."));

	}

}

?>
