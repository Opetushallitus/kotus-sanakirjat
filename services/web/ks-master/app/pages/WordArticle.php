<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * app/pages/WordArticle.php
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * 				Outi Lehtinen ja Jari Vihtari, 2020
*/
?>

<main id="alkuun">

<?php
		// Korjataan näkymälinkit (tulos)listan ensimmäiselle osumalle
		if (preg_match("/p=headwords/", $_SERVER['REQUEST_URI'])) {
			$req_uri = "?" . $this->get("articlepath");
		} else if (preg_match("/p=searchresults/", $_SERVER['REQUEST_URI'])) {
			$qs_link = preg_replace("/p=article/", "p=qs-article", $this->get("articlepath")) . "&keyword=" . $this->get("keyword");
			$req_uri = preg_replace("/p=searchresults/", $qs_link, $_SERVER['REQUEST_URI']);
		} else if (preg_match("/p=advsearchresults/", $_SERVER['REQUEST_URI'])) {
			$as_link = preg_replace("/p=article/", "p=as-article", $this->get("articlepath")) . "&keyword=" . $this->get("keyword");
			$req_uri = preg_replace("/p=advsearchresults/", $qs_link, $_SERVER['REQUEST_URI']);
		} else {
			// poistetaan edellinen näkymäparametri
			$req_uri = preg_replace("/&view=(full|inflection|examples)/", "", $_SERVER['REQUEST_URI']);
		}

		// Upotetaan näkymän vaihtolinkit artikkeliin / 2.12.2019 OL
		$views = "$1<div class=\"article-viewtype\"><span class=\"article-viewtype-caption\">" 
				 . _("Article_Viewtype") . "</span>&nbsp;&nbsp;";
		
		// Perusnäkymä (koko artikkeli)
		if (empty($viewtype) or $viewtype == "full") 
			$views .= _("Article_Viewtype_Full");
		else 
			$views .= "<a href=\"" . $req_uri . "\">" . _("Article_Viewtype_Full") . "</a>";

		$views .= "<span class=\"article-viewtype-sep\">&nbsp;&nbsp;|&nbsp;&nbsp;</span>";

		// Tiivis näkymä
		if ($viewtype == "examples") 
			$views .= _("Article_Viewtype_Examples");
		else 
			$views .= "<a href=\"" . $req_uri . "&amp;view=examples\" title=\"" . _("Article_Viewtype_Examples_Info") . "\">" . _("Article_Viewtype_Examples") . "</a>";
		
		$views .= "<span class=\"article-viewtype-sep\">&nbsp;&nbsp;|&nbsp;&nbsp;</span>";

		/* Uusi taivutusnäkymä / 13.5.2026 OL */
		if ($viewtype == "inflection") 
			$views .= _("Article_Viewtype_Inflection");
		else 
			$views .= "<a href=\"" . $req_uri . "&amp;view=inflection\" title=\"" . _("Article_Viewtype_Inflection_Info") . "\">" . _("Article_Viewtype_Inflection") . "</a>";	
		
		// Uusi aakkosjärjestysnäkymä / 30.4.2026 OL
		// Näytetään vain jos on haettu jotain
		if (!preg_match("/p=article/", $_SERVER['REQUEST_URI'])) {
			$views .= "<span class=\"article-viewtype-sep\">&nbsp;&nbsp;||&nbsp;&nbsp;</span>";
			$views .= "<a href=\"./?" . $this->get("articlepath") . "\"  
						title=\"" . _("Article_Viewtype_DictionaryOrder_Info") .  "\">" . _("Article_Viewtype_DictionaryOrder") . "</a>";
		}
		$views .= "</div>";
		// Näkymälinkit hakusanan tietoja erottavan vaakaviivan alle
		$pattern = "/(<hr class=\"display-para\"\/>)/";
		$articlehtml = preg_replace($pattern, $views, $article);
?>

<article>

<?= $articlehtml; ?>

<div class="jaalinkit">
<span class="jakolinkki">
	<!-- palaute artikkelista sähköpostilla / 30.2.2026 OL -->
	<span aria-hidden="true"><?= _("Feedback") ?>:</span>
	<a href="<?= $cfg['KS']['MAILTO'] . $this->get("word"); ?>"
	title="<?= _("Feedback") ?>"
	aria-label="<?= _("Feedback") ?>">
	<img alt="" src="./pub/img/common/mail.svg"></a>
</span>
<span class="jakolinkki">
	<span aria-hidden="true"><?= _("Share this entry") ?>:</span>
	<button type="button" class="jakonappi"
  		aria-label="<?= _("Share this entry") ?>"
  		title="<?= _("Share this entry") ?>"
  		onclick="copyPermalink(this)"
  		data-url="<?= $this->get("permalink"); ?>">
  		<img alt="" src="./pub/img/common/link.svg"/>
	</button>
	<span id="copy-status" aria-live="polite"></span>
</span>
</div>

</article>

<script type="text/javascript">
	// linkin kopiointi leikepöydälle
	function copyPermalink(button) {
  		//const url = button.dataset.url;
  		//navigator.clipboard.writeText(url)
		const url = new URL(button.dataset.url, window.location.origin);
		navigator.clipboard.writeText(url.href)
    	.then(() => {
      			document.getElementById('copy-status').textContent =
        		'<?= _("Link copied to clipboard") ?>';
    		})
    	.catch(() => {
      		document.getElementById('copy-status').textContent =
        	'<?= _("Failed to copy link to clipboard") ?>';
    	});
	}
	// Show the hash target if it exists
	function showHashTarget(hash) {
		kohde = hash.replace("#", "");
		if (kohde) { // if-lauseke, jotta jos kohdetta ei ole, niin ei tule virhettä
			kohdeElem = document.getElementById(kohde);
			if (kohdeElem) { 
				kohdeElem.style.color = "#CC3300";
			}
		}
	}
	window.addEventListener("load", function() {
		showHashTarget(window.location.hash);
	});
</script>

</main>