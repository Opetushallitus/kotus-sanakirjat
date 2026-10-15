<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * app/pages/AdvancedSearchForm.php
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * 				Outi Lehtinen ja Jari Vihtari, 2021
 */
?>
<!--
<div class="etusivu kirjanmerkki">
-->

<main id="alkuun">

<div class="teksti">
<h1><?= _("AdvancedSearch_Page") ?></h1>

<details open>
	<summary class="haitarilabel">Pikaohje</summary>
	<div>
	Tarkennetussa haussa voi käyttää niin sanottuja jokerimerkkejä eli kysymysmerkkiä ja tähtimerkkiä. 
	Kysymysmerkki (?) korvaa minkä tahansa merkin. 
	Tähtimerkki (*) vastaa mitä tahansa merkkiä tai useamman merkin jonoa. 
	</p>
	<p>
	<a href="./?p=help" target="_blank" 
	aria-label="Yksityiskohtaiset hakuohjeet, <?= _("Link_OpenNewWin") ?>">Yksityiskohtaiset hakuohjeet</a> (avautuu uuteen ikkunaan)
	</p>
	</div>
</details>

<h2><?= _("AdvancedSearchForm_Caption") ?></h2>


<!-- TARKENNETUN HAUN LOMAKE -->

<div class="tabs-container">

<?php
$searchmode = "advsearch";
if (isset($_SESSION['advsearchtype']) && $_SESSION['advsearchtype'] == "kwicsearch") {
	$searchmode = "kwicsearch";
}
?>

<details name="tabs" class="hakutab"<?php echo $searchmode == "advsearch" ? " open" : ""; ?>>
    <summary class="haitarilabel"><?= _("AdvancedSearchForm_Keyword_Legend") ?></summary>

	<!-- TARKENNETTU HAKU: ARTIKKELIHAKU -->
	<form id="search-form" action="./?p=advsearchresults" method="post">
	
	<div class="search-form-grid">
		<label for="search-form-keyword"><?= _("AdvancedSearchForm_Keyword") ?></label>
		<div>
			<input type="text" id="search-form-keyword" name="keyword" value="" />

				<!-- poistetaan 9.10.2026 jv:
				aria-required="true" required  
				oninvalid="this.setCustomValidity('<?= _("AdvancedSearchForm_Keyword_Required") ?>')" onchange="this.setCustomValidity('')" 
				-->
				
			<select id="search-form-hwfrom" name="hwfrom">
				<option value="1"><?= _("AdvancedSearchForm_FromAllWords") ?></option>
				<!-- Poistettu osahaku käytöstä, ainakin toistaiseksi / 21.9.2026 OL
				<option value="2"><?= _("AdvancedSearchForm_FromCompoundParts") ?></option>
				-->
				<option value="3"><?= _("AdvancedSearchForm_FromCompounds") ?></option>
				<option value="4"><?= _("AdvancedSearchForm_FromNotCompounds") ?></option>
			</select>
			<a href="./?p=help#hakuohje-hakusanat" target="_blank" 
			aria-label="<?= _("AdvancedSearchForm_Help") ?> <?= _("Link_OpenNewWin") ?>"
			title="<?= _("AdvancedSearchForm_Help") ?> <?= _("Link_OpenNewWin") ?>">
				<img src="./pub/img/common/ohjeet.png" alt="<?= _("AdvancedSearchForm_Help") ?>" class="iconimg" />
			</a>
		</div>
		<label for="search-form-tindex"><?= _("AdvancedSearchForm_TIndex") ?></label>
		<div>
			<select id="search-form-tindex" name="tindex">
				<?php foreach ($tindex as $key => $value) {
					echo "<option value=\"$key\">$value</option>";
				} ?>
			</select>
		</div>
		<label for="search-form-avindex"><?= _("AdvancedSearchForm_AVIndex") ?></label>
		<div>
			<select id="search-form-avindex" name="avindex">
				<?php foreach ($avindex as $key => $value) {
					echo "<option value=\"$key\">$value</option>";
				} ?>
			</select>
		</div>
		<label for="search-form-word-group"><?= _("AdvancedSearchForm_WordGroup") ?></label>
		<div>
			<select id="search-form-word-group" name="word_group">
				<?php foreach ($word_groups as $key => $value) {
					echo "<option value=\"$key\">$value</option>";
				} ?>
			</select>
		</div>

		<label for="search-form-usage-scope"><?= _("AdvancedSearchForm_UsageScope") ?></label>
		<div>
			<select id="search-form-usage-scope" name="usage_scope">
				<?php foreach ($usage_scopes as $key => $value) {
					echo "<option value=\"$key\">$value</option>";
				} ?>
			</select>
		</div>
	</div>
	<span id="search-form-error"></span>
	<div>&nbsp;</div>
	<div class="search-form-buttons">
		<input type="submit" value="<?= _("AdvancedSearchForm_SearchButton") ?>" />
		<input type="reset" value="<?= _("AdvancedSearchForm_ResetButton") ?>" />
	</div>
	
	</form>
</details>

<!-- TARKENNETTU HAKU: KWIC-HAKU -->
<details name="tabs" class="hakutab"<?php echo $searchmode == "kwicsearch" ? " open" : ""; ?>>
    <summary class="haitarilabel"><?= _("AdvancedSearchForm_FullText_Legend") ?></summary>

	<form id="search-form2" action="./?p=advkwicresults" method="post">

	<div>
		<label for="search-form-fulltext"><?= _("AdvancedSearchForm_FullText") ?></label>
			<input type="text" id="search-form-fulltext" name="fulltext" />
			<select id="search-form-fullfrom" name="fullfrom">
				<option value="1"><?= _("AdvancedSearchForm_FullFromAll") ?></option>
				<option value="2"><?= _("AdvancedSearchForm_FullFromDefinitions") ?></option>
				<option value="3"><?= _("AdvancedSearchForm_FullFromExamples") ?></option>
			</select>
			<a href="./?p=help#hakuohje-kokoteksti" target="_blank" 
				aria-label="<?= _("AdvancedSearchForm_Help") ?> <?= _("Link_OpenNewWin") ?>" 
				title="<?= _("AdvancedSearchForm_Help") ?> <?= _("Link_OpenNewWin") ?>">
				<img src="./pub/img/common/ohjeet.png" alt="<?= _("AdvancedSearchForm_Help") ?>" class="iconimg" />
			</a>
			<span id="search-form-fulltext-error"></span>
	</div>
				
	<div class="search-form-checkbox">
		<input type="checkbox" id="search-form-lemma" name="lemma" />
		<label for="search-form-lemma" id="search-form-lemma-label"><?= _("AdvancedSearchForm_Lemma") ?></label>&nbsp;<a href="./?p=help#hakuohje-lemma" 
				target="_blank" aria-label="<?= _("AdvancedSearchForm_Help") ?> <?= _("Link_OpenNewWin") ?>" 
				title="<?= _("AdvancedSearchForm_Help") ?> <?= _("Link_OpenNewWin") ?>"><img src="./pub/img/common/ohjeet.png" alt="Ohje" class="iconimg">
		</a>
	</div>

	<div>&nbsp;</div>
	<div class="search-form-buttons">
		<input type="submit" value="<?= _("AdvancedSearchForm_SearchButton") ?>" />
		<input type="reset" value="<?= _("AdvancedSearchForm_ResetButton") ?>" />
	</div>

</form>
</details>
<!--
<div>&nbsp;</div>
<div class="search-form-buttons">
	<input type="submit" id="search-form-submit" value="<?= _("AdvancedSearchForm_SearchButton") ?>" />
	<input type="reset" id="search-form-reset" value="<?= _("AdvancedSearchForm_ResetButton") ?>" />
</div>

</form>
-->
</div>

</main>

<script>
/* Korvaava toiminto  window.onload-eventille. Näitä voi olla useampia päinvastoin kuin window.onload:ia,
   toimii myös IE:llä (mutta ei muinaisilla versioilla)  */
/*window.addEventListener("load", function() {
	var aform = document.getElementById("search-form");
	lomakekuuntelija(aform);
});*/
</script>