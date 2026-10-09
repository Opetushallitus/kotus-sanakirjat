<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * pub/PopupHeader.php
 * 
 * Copyright:	Institute for the Languages of Finland
 * Author:		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?= $title ?></title>
	<link rel="shortcut icon" href="./pub/favicon.ico" />
	<link rel="stylesheet" type="text/css" media="all" href="pub/css/popup.css" />
</head>

<body>

<div id="fo-container">

<div id="bar-top">
	<div id="bar-top-logo"><img src="/kuvat/kotus-logo-verkkosanakirja-suomi.png" alt="" border="0" /></div>
	
	<?php if ($cfg['LCL']['LANGBAR']) { ?>
	<div id="bar-top-langlinks">
		<?php
			// Go through locales and make links for language bar.
			foreach($cfg['LCL']['ENABLED'] as $code => $name) {

				// Echo available languages as a plain text or with a link,
				// depending if the locale in turn is currently in use.
				if ($_SESSION['KS_lang'] != $code) {
					echo "<a href=\"./?p=chlang&amp;l=$code\">$name</a>";
				} else {
					echo "<b>$name</b>";
				}

				// Put a separator between links except after the last link.
				if (end($cfg['LCL']['ENABLED']) != $name) echo " | ";
			}
		?>
	</div>
	<?php } ?>
</div>

<div id="header">
	<img src="./pub/img/popup_header.png" alt="" border="0" />
</div>
