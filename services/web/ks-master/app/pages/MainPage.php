<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * app/pages/MainPage.php
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * 				Outi Lehtinen ja Jari Vihtari, 2023
*/
?>

<!-- SISÄLTÖOSA -->

<div class="etusivu">

<main>

	<header class="logo">
		<img src="/img/logot/ks-logo.png" alt="Kielitoimiston sanakirjan logo" />
		<h1><?= _("DICT_Title") ?></h1>
	</header>

<form autocomplete="off" class="hakuruutu" action="./?p=searchresults" method="post">
<div class="hakuehdotus" aria-live="polite">
<input type="text" name="keyword" autofocus="autofocus" 
placeholder="<?= _("search headword") ?>" id="hakuinput" /><button type="reset" aria-label="Tyhjennä">&nbsp;</button><button type="submit" aria-label="Hae">&nbsp;</button>
</div>
</form>

<div class="aakkoslinkit" id="aakkoslinkit_div">
<?php
// Poistetaan käytöstä php-versio aakkoslinkkien tuottamisesta varten, tuotetaan sen sijaan javascriptillä (4.6.2026 jv)
// new SearchController("getHeadwordsFirstAllByPrefix");
?>
	<a id="prefix_0" class="aakkonen">0–9</a>
	<a id="prefix_a" class="aakkonen">A</a>
	<a id="prefix_b" class="aakkonen">B</a>
	<a id="prefix_c" class="aakkonen">C</a>
	<a id="prefix_d" class="aakkonen">D</a>
	<a id="prefix_e" class="aakkonen">E</a>
	<a id="prefix_f" class="aakkonen">F</a>
	<a id="prefix_g" class="aakkonen">G</a>
	<a id="prefix_h" class="aakkonen">H</a>
	<a id="prefix_i" class="aakkonen">I</a>
	<a id="prefix_j" class="aakkonen">J</a>
	<a id="prefix_k" class="aakkonen">K</a>
	<a id="prefix_l" class="aakkonen">L</a>
	<a id="prefix_m" class="aakkonen">M</a>
	<a id="prefix_n" class="aakkonen">N</a>
	<a id="prefix_o" class="aakkonen">O</a>
	<a id="prefix_p" class="aakkonen">P</a>
	<a id="prefix_q" class="aakkonen">Q</a>
	<a id="prefix_r" class="aakkonen">R</a>
	<a id="prefix_s" class="aakkonen">S</a>
	<a id="prefix_t" class="aakkonen">T</a>
	<a id="prefix_u" class="aakkonen">U</a>
	<a id="prefix_v" class="aakkonen">VW</a>
	<a id="prefix_x" class="aakkonen">X</a>
	<a id="prefix_y" class="aakkonen">Y</a>
	<a id="prefix_z" class="aakkonen">Z</a>
	<a id="prefix_å" class="aakkonen">Å</a>
	<a id="prefix_ä" class="aakkonen">Ä</a>
	<a id="prefix_ö" class="aakkonen">Ö</a>
</div>

<div class="etusivun_teksti">

	<p>
	Lyhyt esittely
	</p>
	<p>
	Tällä hetkellä sanakirjassa on <?= $word_count ?> artikkelia.</p>

	<p>Päivitettävä julkaisu.</p>

	<p>Tuorein päivitys <?= $last_update ?>.</p>

</div>

</main>


</div>
