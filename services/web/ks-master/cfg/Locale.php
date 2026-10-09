<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * cfg/Locale.php
 *
 * Configuration settings for locales (language translations)
 *
 * Configurations are stored in an associative arrays. Each configuration part has it's own short identifier,
 * which makes it easier to group all same kind of settings below one big multidimensional array called $cfg.
 *
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * Copyright: 	Institute for the Languages of Finland
*/

$cfg['LCL'] = array(
	// Default locale for the website
	'DEFAULT'	=> 'fi_FI',

	// Enabled locales, will be shown on language bar
	'ENABLED'	=> array(
							'fi_FI' => 'FI',
							'sv_SE' => 'SV',
							'en_US' => 'EN'
						),

	// Define codeset for locale domains.
	// For convience, all locales must use same codeset.
	'CODESET'	=> 'UTF-8',

	// Wheter to show language bar on top right.
	'LANGBAR'		=> true

);

?>
