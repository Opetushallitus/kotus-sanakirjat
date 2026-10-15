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

/**
 * General-purpose input sanitization.
 * 
 * Use sanitize_input() to clean user-provided values.
 *
 * Types used by Kielitoimiston sanakirja:
 * 	'string' (default),
 * 	'ks_id',
 * 	'int',
 * 	and arrays (recursive).
 * 
 * Options:
 *   - max_length: int|null  - truncate result
 *   - default: mixed        - return value when validation fails
 *   - allow_tags: string|false - for html/string: allowed tags for strip_tags
 *   - escape_html: bool     - whether to run htmlspecialchars() on strings
 *   - subtype: string       - when sanitizing arrays, element type
 *
 * Example usage:
 *   $name = sanitize_input($_POST['name'], 'string', ['max_length' => 200]);
 *   $age  = sanitize_input($_GET['age'], 'int', ['default' => 0]);
 *   $html = sanitize_input($_POST['content'], 'html', ['allow_tags' => '<p><a><strong>']);
 *
 * Author:	Outi Lehtinen & Jari Vihtari, 2026
 */
function sanitize_input($value, $type = 'string', $options = array()) {

	$opts = array_merge(array(
		'max_length'  => null,   // truncate result
		'default'     => null,   // default return value when validation fails
		'allow_tags'  => false,  // for html/string: string of allowed tags or false
		'escape_html' => false,  // whether to run htmlspecialchars() on strings
		'subtype'     => null,   // when sanitizing arrays: element type
	), $options);

	// Handle arrays recursively
	if (is_array($value)) {
		$out = array();
		$subtype = $opts['subtype'] ? $opts['subtype'] : 'string';
		foreach ($value as $key => $v) {
			$out[$key] = sanitize_input($v, $subtype, $options);
		}
		return $out;
	}

	// Switch by requested type
	switch ($type) {
		case 'ks_id':
			// Sanitazion of dictionary ID
			$v = (string)$value;
			$v = trim($v);
			// remove whitespace, NULL bytes and other control characters 
			$v = preg_replace('/[\s\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $v);
			if ($opts['escape_html']) {
				$v = htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
			}
			if (preg_match('/^KS_[^\s]+$', $v) == false) {
				return null;
			}
			return $v;
		case 'int':
			if ($value === '' || $value === null) {
				return $opts['default'];
			}
			if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
				return (int)$value;
			}
			if (is_numeric($value)) {
				return (int)$value;
			}
			return $opts['default'];
		// Default case: treat as string
		case 'string':
		default:
			$v = (string)$value;
			$v = trim($v);
			// remove NULL bytes and other control characters except newline/tab
			$v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $v);
			if ($opts['allow_tags']) {
				$v = strip_tags($v, $opts['allow_tags']);
			}
			else {
				$v = strip_tags($v);
			}
			if ($opts['escape_html']) {
				$v = htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
			}
			if ($opts['max_length']) {
				$v = mb_substr($v, 0, (int)$opts['max_length']);
			}
			return $v === '' ? $opts['default'] : $v;
	}
}


?>
