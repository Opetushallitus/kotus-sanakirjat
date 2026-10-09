<?php

/**
 * inc/Locale.php
 *
 * Initialize and set up locales for multilingual interface.
 *
 * Uses PHP's gettext extension.
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author:		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
*/

include "cfg/Locale.php";

// Shorten session variable name to $langid as a reference.
$lcl_code =& $_SESSION['KS_lang'];

// Checks if the given locale exists in enabled locales array.
if (!array_key_exists($lcl_code, $cfg['LCL']['ENABLED'])) {

	// Put the default locale in use if the locale hasn't been set yet by
	// app/user ($lang is empty) or if the locale name in use doesn't exist in enabled locales.
	$lcl_code = $cfg['LCL']['DEFAULT'];

}

// Get absolute locales (translations) path.
$path = $cfg['KS']['BASE_PATH'] . $cfg['KS']['DIR_SEP'] . "inc"
	  . $cfg['KS']['DIR_SEP'] . "locale";

foreach ($cfg['LCL']['ENABLED'] as $code => $foo) {
	// Specify location of each locale and set appropriate codeset.
	bindtextdomain($code, $path);
	bind_textdomain_codeset($code, $cfg['LCL']['CODESET']);
}

// Choose domain.
textdomain($lcl_code);

// Set some environment variables and the locale in use.
putenv("LANG=$lcl_code");
putenv("LC_MESSAGES=$lcl_code");
setlocale(LC_MESSAGES, $lcl_code);

?>
