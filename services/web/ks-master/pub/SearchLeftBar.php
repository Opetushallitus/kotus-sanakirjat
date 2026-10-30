<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * pub/LeftBar.php
 * 
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * 				Outi Lehtinen ja Jari Vihtari, 2019
*/
?>

<aside class="sivupalkki hakulista">

<header class="logo">
	<a href="./?p=main">
		<img src="./pub/img/ks-logo.png" alt="Kielitoimiston sanakirjan logo" width="285" height="124" />
	</a>
	<div class="nimeke1"><?= _("DICT_Title") ?></div>
</header>

<!-- Hakuruutu aina näkyvissä / 2.6.2020 OL -->
<form autocomplete="off" class="hakuruutu" action="./?p=searchresults" method="post">
<div class="hakuehdotus" aria-live="polite">
<input type="text" name="keyword"  
placeholder="<?= _("search headword") ?>"
id="hakuinput" /><button type="reset" aria-label="Tyhjennä">&nbsp;</button><button type="submit" aria-label="Hae">&nbsp;</button>
</div>
</form>

<!-- 2.6.2020 OL -->
<details id="hakutuloslista">
    <!-- Siirretty hakuinfon sisältö summaryyn / 12.6.2020 OL -->
    <summary>
        <?= _("QuickSearchResults_Caption") .
        " (" . "$count " . _("QuickSearchResults_Hits") . ")" .
        ": $keyword " ?>
    </summary>
	<div class="hakuosa">

		<!-- uusi listatoteutus / 7.7.2020 OL-->
		<div class="hakemisto" id="hlista" 
			onscroll="skrollaa(this, '<?= isset($keyword) ? htmlspecialchars($keyword, ENT_QUOTES) : null ?>', false, 
			'<?= isset($list_id) ? $list_id : 1 ?>')" style=""></div>

	</div>
</details>


<!-- Päivitetään hakusanalistan kohta nykyisellä artikkelilla / 19.7.2019 OL -->
<script type="text/javascript">
	
	window.addEventListener("load", function() {
		var inputElem = document.getElementById("hakuinput");
		if (inputElem) {
			inputElem.value = '<?= isset($keyword) ? htmlspecialchars($keyword) : "" ?>';
		}
	});
	

	// Hakusanoissa voi olla heittomerkkejä, jotka pitää koodata / 30.10.2026 OL
    haeHakusanatListaan(<?= isset($list_id) ? $list_id : 1 ?>,
    '<?= isset($keyword) ? htmlspecialchars($keyword, ENT_QUOTES) : "" ?>', false);

	// Lisätään details-elementille kuuntelija, joka tuhoaa itsensä ensimmäisen käytön jälkeen (kai?)
	document.getElementById("hakutuloslista").addEventListener('toggle', detailsUpdate);
</script>

</aside>
