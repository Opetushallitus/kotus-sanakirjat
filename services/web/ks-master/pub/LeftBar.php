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
		<img src="/img/logot/ks-logo.png" alt="Kielitoimiston sanakirjan logo" width="285" height="124" />
	</a>
	<div class="nimeke1"><?= _("DICT_Title") ?></div>
</header>

<!-- Hakuruutu aina näkyvissä / 2.6.2020 OL -->
<form id="pikahaku" autocomplete="off" class="hakuruutu" action="./?p=searchresults" method="post">
	<div class="hakuehdotus" aria-live="polite">
		<input type="text" name="keyword" onfocus="this.select();" placeholder="<?= _("search headword") ?>"
<?php
	if (isset($keyword)) {
		echo ' value="' . htmlspecialchars($keyword) . '"';
	}
?>
id="hakuinput" /><button type="reset" aria-label="Tyhjennä">&nbsp;</button><button type="submit" aria-label="Hae">&nbsp;</button>
</div>
</form>

<!-- 2.6.2020 OL -->
<details id="hakutuloslista">
<summary><?= _("Headwords") ?></summary>

<!-- id-lisätty / 5.3.2020 OL -->
<div class="hakuosa">

<!-- Kokeillaan aakkoslinkkejä nappien sijaan 25.5.2026 JV 
<div class="aakkoslinkit">
	<button class="aakkonen" onclick="haeViitekohdePrefix('0')">0–9</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('a')">A</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('b')">B</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('c')">C</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('d')">D</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('e')">E</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('f')">F</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('g')">G</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('h')">H</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('i')">I</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('j')">J</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('k')">K</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('l')">L</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('m')">M</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('n')">N</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('o')">O</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('p')">P</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('q')">Q</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('r')">R</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('s')">S</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('t')">T</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('u')">U</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('v')">VW</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('x')">X</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('y')">Y</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('z')">Z</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('å')">Å</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('ä')">Ä</button>
	<button class="aakkonen" onclick="haeViitekohdePrefix('ö')">Ö</button>
</div>
-->

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

<div class="hakemisto" id="hlista" 
    onscroll="skrollaa(this, false, false, '<?= isset($list_id) ? $list_id : 1 ?>')" style=""></div>
</div>

</details>


<script type="text/javascript">

// uusi listatoteutus / 7.7.2020 OL
haeHakusanatListaan(<?= isset($list_id) ? $list_id : 1 ?>, false, false);

// Lisätään details-elementille kuuntelija, joka tuhoaa itsensä ensimmäisen käytön jälkeen
document.getElementById("hakutuloslista").addEventListener('toggle', detailsUpdate);

</script>

</aside>
