<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * pub/Footer.php
 * 
 * Copyright:	Institute for the Languages of Finland
 * Author:		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 *				Outi Lehtinen ja Jari Vihtari, 2019
*/
?>

<!-- KIVIJALKA -->

<footer class="kivijalka">

<div>
<img src="./pub/img/common/kotus-suomi-valkoinen.png" alt="Kotimaisten kielten keskus" width="285" height="50" />
<p>
© <?= _("Footer_Kotus") ?> (<a target="_blank" href="<?= _("Footer_Kotus_www") ?>" 
aria-label="<?= _("Footer_Kotus_www") ?> <?= _("Link_OpenNewWin") ?>"><?= _("Footer_Kotus_www") ?></a>)
</p>
<p>
<a target="_blank" href="https://www.kotus.fi/kotus/kotus_verkossa/evastekaytanto" 
 title="<?= _("Footer_Cookies") ?> <?= _("Link_OpenNewWin") ?>" 
 aria-label="<?= _("Footer_Cookies") ?> <?= _("Link_OpenNewWin") ?>"><?= _("Footer_Cookies") ?></a><br />
<a target="_blank" href="https://www.kotus.fi/kotus/kotus_verkossa/saavutettavuusseloste" 
 title="<?= _("Footer_Accesibility") ?> <?= _("Link_OpenNewWin") ?>"
 aria-label="<?= _("Footer_Accesibility") ?> <?= _("Link_OpenNewWin") ?>"><?= _("Footer_Accesibility") ?></a><br />
</p>
</div>

<div>
<p><strong><?= _("Footer_Bibliography") ?></strong><br />
Kotimaisten kielten keskuksen verkkojulkaisuja 35.<br />
URN:NBN:fi:kotus-201433<br />
ISSN 2323-3370</p>
<p>
<?= _("Footer_Last_Updated") ?> <?= $last_update ?></p>
<p>
<a rel="license" target="_blank" href="<?= _("Footer_CC4_license_link") ?>" 
 title="Creative Commons -lisenssi <?= _("Link_OpenNewWin") ?>" 
 aria-label="Creative Commons -lisenssi <?= _("Link_OpenNewWin") ?>">
<img alt="Creative Commons -lisenssi" style="border-width:0" src="https://i.creativecommons.org/l/by/4.0/80x15.png" /></a><br />
<?= _("Footer_Published_with") ?> 
<a target="_blank" rel="license" href="<?= _("Footer_CC4_license_link") ?>" 
 title="<?= _("Footer_CC4_International") ?> <?= _("Link_OpenNewWin") ?>"
 aria-label="<?= _("Footer_CC4_International") ?> <?= _("Link_OpenNewWin") ?>">
<?= _("Footer_CC4_International") ?></a> <?= _("Footer_Licence") ?>.
<br />
</p>
</div>

</footer>

<script>
/* Korvaava toiminto  window.onload-eventille. Näitä voi olla useampia päinvastoin kuin window.onload:ia,
   toimii myös IE:llä (mutta ei muinaisilla versioilla)  */
window.addEventListener("load", function() {
    navimenu("paanavi");
// Ei sittenkään piiloteta kwictuloksia leveällä, pois: piilodetails("hakutuloslista"<?php
//// true if the page is kwic-article
//if (isset($_GET['p']) && $_GET['p'] == "kwic-article") {
//    echo ', true';
//}
//?>);
	piilodetails("hakutuloslista");
});

/* Valikon sulkeminen. Käytetään linkitettäessä uudelle välilehdelle avautuvaan sivuun. / 8.12.2020 OL */
function closeThisToo(divId) {
        document.getElementById(divId).style.display = "none";
        document.getElementById("navimenu_overlay").style.display = "none";
        return true;
}
</script>

<!-- Pikahaun ehdotusten toiminta -->
<script type="text/javascript" src="pub/js/ajaxautocomplete.js"></script>

</body>
</html>
