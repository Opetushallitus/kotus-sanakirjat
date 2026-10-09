<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * pub/Navi.php - Päänavigaatio
 * 
 * Copyright:	Institute for the Languages of Finland
 * Authors:		Outi Lehtinen ja Jari Vihtari, 2020
*/
?>
<?php if (isset($etusivu)) { 
	// ei hyppylinkkia
	}
	else {
		echo "<nav class=\"hyppylinkki\"><a href=\"#alkuun\">Suoraan sisältöön</a></nav>";
	}
?>

<!-- Yläpalkki -->
<header class="ylapalkki">

<nav class="kielinavigointi" aria-label="kielivalinta">
<!-- Kielivalinnat, jos on 
<?php if ($cfg['LCL']['LANGBAR']) {
	// Go through locales and make links for language bar.
	foreach($cfg['LCL']['ENABLED'] as $code => $name) {
		// Echo available languages as a plain text or with a link,
		// depending if the locale in turn is currently in use.
		if ($_SESSION['KS_lang'] != $code) {
			echo "<a href=\"./?p=chlang&amp;l=$code\">$name</a>";
		} else {
			echo "<a href=\"./?p=chlang&amp;l=$code\">$name</a>";
		}
	}
} 
?>
-->
</nav>

<!-- Suurennuslasi- ja sämpylävalikot -->
<div class="valikot">

<!--
<nav class="navigointi" aria-label="selaus">
<a href="./?p=headwords" class="tekstimenubutton"><img src="/img/selaus.svg" alt=""/>&#160;<?= _("Navi_Browse") ?></a>
</nav>
-->

<!-- UUSI hakusivu = tarkennetun haun lomake + sivupalkki / 9.6.2020 OL -->
<!-- Tekstilliset navinappulat / 24.11.2020 OL -->
<nav class="navigointi" aria-label="haku">
<a href="./?p=advsearchform" class="tekstimenubutton"><img src="/img/suurennuslasi.svg" alt=""/>&#160;<?= _("Navi_Search") ?></a>
</nav>

<nav class="navigointi" aria-label="valikko">
<button id="paanavi_open" class="tekstimenubutton"><img src="/img/sampyla.svg" alt=""/>&#160;<?= _("Navi_Menu") ?></button>
<div id="paanavi" class="navivalikko">
<button id="paanavi_close" name="navilistan_sulku" class="tekstimenubutton">
<img src="/img/rasti_valkoinen.svg" alt=""/>&#160;<?= _("Navi_Close") ?></button>
<ul>
<li><a href="./?p=main"><?= _("Navi_Mainpage") ?></a></li>
<li><a href="./?p=help"><?= _("Navi_Help_Search") ?></a></li>
<li><a href="./?p=help&sp=2"><?= _("Navi_Help_Read") ?></a></li>
<li><a href="./?p=abbreviations"><?= _("Navi_Abbreviations") ?></a></li>
<li><a href="./?p=background"><?= _("Navi_Background") ?></a></li>
<li><a onclick="return closeThisToo('paanavi')"
                href="<?= $cfg['KS']['FEEDBACK_URL'] ?>" target="_blank"
                aria-label="<?= _("Navi_Feedback") ?>, <?= _("Link_OpenNewWin") ?>"><?= _("Navi_Feedback") ?></a>
</li>
</ul>
</div>
</nav>

</div>

</header>
