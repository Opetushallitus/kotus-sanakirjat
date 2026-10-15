<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * pub/Header.php
 * 
 * Copyright:	Institute for the Languages of Finland
 * Author:		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * 				Outi Lehtinen ja Jari Vihtari, 2019
*/
?>
<!doctype html>
<html lang="fi">
<head>
<meta charset="utf-8" />

<!-- Tämän pitäisi auttaa sivua skaalautumaan paremmin puhelimissa -->
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= $title ?></title>

<!-- favicon/kuvake -->
<link rel="shortcut icon" href="./pub/img/favicon.ico" />

<!-- Metatiedot -->
<meta name="description" content="<?= $title ?>">
<!-- Open Graph -->
<meta property="og:title" content="<?= $title ?>" />
<meta property="og:description" content="<?= $title ?>"/>
<meta property="og:url" content="<?= $cfg['KS']['KS_URL'] ?>" />
<meta property="og:image" content="/img/logot/ks-logo.png" />
<meta property="og:site_name" content="<?= $title ?>" />

<!-- Google fonts -->
<link href="https://fonts.googleapis.com/css?family=Noto+Serif:400,400i|Open+Sans:300,400,400i,700&display=swap&subset=latin-ext" rel="stylesheet" /> 
	
<!-- Tyylisivut -->
<!-- YHTEISET -->
<link rel="stylesheet" type="text/css" media="all" href="pub/css/julkaisumalli-style.css?cache=no" />
<!-- SANAKIRJAKOHTAISET -->
<link rel="stylesheet" type="text/css" media="all" href="pub/css/dict_style.css" />
<link rel="stylesheet" type="text/css" media="all" href="pub/css/article.css" />
<!-- Tulostus -->
<link rel="stylesheet" type="text/css" media="print" href="pub/css/julkaisumalli-print.css" />

<!-- Javascript -->
<!-- YHTEISET -->
<script type="text/javascript" src="pub/js/julkaisumalli-ks.js"></script>

<?php
		// Attach all scripts, that have been set in controller.
		// It must *always* be an array (because of foreach)
		if (isset($scripts) && is_array($scripts)) {
			foreach($scripts as $file) {
				echo "<script type=\"text/javascript\" src=\"pub/js/$file\"></script>\n";
				
				// Don't echo tabulator if last file is in turn.
				if (end($scripts) != $file) echo "\t";
			}
		}
	?>

<!-- Matomon seuranta kommentoitu pois toistaiseksi / 15.10.2026 OL -->
<!-- ?php include_once("analyticstracking.php") ?-->

</head>

<body>


