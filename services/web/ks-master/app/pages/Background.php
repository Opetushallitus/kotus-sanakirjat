<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * app/pages/Background.php
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * 				Outi Lehtinen ja Jari Vihtari, 2020
*/
?>

<main id="alkuun">

<article class="teksti">
<h1><?= _("Background_Caption") ?></h1>

<p>
</p>

<h2>Tietoa sanakirjasta</h2>
<ul>
<li><a target="_blank"
href=""
aria-label="<?= _("Link_OpenNewWin") ?>: Kielitoimiston sanakirjan esittely">Kielitoimiston sanakirjan esittely</a>
</li>
<li>
Kielitoimiston sanakirjan toimittajat</li>
<li>
Anna palautetta Kielitoimiston sanakirjasta</li>
</ul>

<h2>Jotain muuta vielä</h2>
<ul>
<li></li>
<li></li>
</ul>

<p>
Kielitoimiston sanakirjaan viitataan seuraavasti:
</p>
<blockquote>
Kielitoimiston sanakirja. <?= preg_replace('/^\d+\.\d+\./','', $last_update) ?>.
Helsinki: Kotimaisten kielten keskuksen verkkojulkaisuja 35. URN:NBN:fi:kotus-201433. 
Päivitettävä julkaisu. Päivitetty <?= $last_update ?> [viitattu xx.xx.xxxx]. 
Saatavissa <?= $cfg['KS']['KS_URL'] ?>.
</blockquote>

<p>
Sana-artikkeleihin viitataan kunkin artikkelin hakusanalla ja www-osoitteella. 
Viittauskelpoisen linkin artikkeliin voi kopioida kunkin artikkelin lopussa olevasta Linkki artikkeliin -kuvakkeesta. 
</p>

</article>

</main>
