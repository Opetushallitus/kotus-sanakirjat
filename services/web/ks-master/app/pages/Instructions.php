<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * app/pages/Instructions.php
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * 				Outi Lehtinen ja Jari Vihtari, 2023
 */
?>
<main id="alkuun">

<article class="teksti">
<h1 id="hakuohje-alku"><?= _("Instructions_Search_Caption") ?></h1>

<p>
Kielitoimiston sanakirjasta voi tehdä monenlaisia hakuja:
<ul>
<li>Perushaussa etsitään haettavaa sanaa hakusanoista.</li>
<li>Tarkennettu haku voidaan kohdistaa esimerkiksi sana-artikkeleiden sanaluokkiin, merkityksenselitteisiin ja esimerkkeihin.</li>
<li>Lisäksi hakusanoja voi etsiä selaamalla hakusanalistaa.</li>
</ul>
</p>
<p>
Perushakukenttä (”kirjoita hakusana”) on sanakirjan jokaisella sivulla. Tarkennettu haku on hakusivulla. 
Hakusivu avautuu SUURENNUSLASI + HAKU -kuvakkeesta.
</p>
<p>
Sekä perushaussa että tarkennetussa haussa voi käyttää niin sanottuja jokerimerkkejä eli kysymysmerkkiä ja tähtimerkkiä. 
Kysymysmerkki (?) korvaa minkä tahansa yksittäisen merkin. Tähtimerkki (*) vastaa mitä tahansa merkkiä tai useamman merkin jonoa. 
Niin kysymys- kuin tähtimerkinkin voi kirjoittaa mihin kohtaan sanaa hyvänsä. 
Kysymysmerkkiä ja tähtimerkkiä voi käyttää myös samanaikaisesti.
</p>
<p>
Haut eivät ota huomioon isojen ja pienten kirjainten eroa. Jos esimerkiksi perushaussa tehdään haku ”mari”, 
saadaan hakutuloslistaan myös sana-artikkeli <strong>Mariatar</strong>.
</p>
<p>
Sanakirja on responsiivinen, eli näkymä mukautuu käytettävän laitteen mukaan. 
</p>

<ul>
<li><a href="#hakuohje-perushaku">Perushaku</a></li>
<li><a href="#hakuohje-tulokset">Perushaun tulokset</a></li>
<li><a href="#hakuohje-hakusanalista">Hakusanalista</a></li>
<li><a href="#hakuohje-tarkennettu">Tarkennettu haku</a></li>
</ul>

<section>
<h2 id="hakuohje-perushaku">Perushaku</h2>

<p>
Sanakirjasta voi tehdä perushakuja kirjoittamalla sanan hakukenttään (”kirjoita hakusana”). Perushaku kohdistuu sanakirjan hakusanoihin eli sanoihin, joista sanakirjassa on sana-artikkeli. Perushaku ei ulotu artikkelinsisäisiin alihakusanoihin. 
</p>
<p>
Perushakukenttä on sanakirjan jokaisella sivulla. Useimmilla sivuilla (ei kuitenkaan etu- tai hakutulossivulla) on mahdollista selata aakkosjärjestyksessä olevaa hakusanojen listaa. Kun tekee haun, näkyviin tulee hakusanalistan asemesta aakkostettu hakutuloslista. 
</p>
<p>
Kun alat kirjoittaa sanaa perushaun hakukenttään, sanakirja ehdottaa mahdollisia sanoja. Perushaussakin toimivat jokerimerkit, 
joten esim. <em>kirja</em>-loppuisia yhdyssanoja voi hakea merkkijonolla <em>*kirja</em>. 
</p>
<p>
Kun olet kirjoittanut hakukenttään sanan tai sanan osan, paina ENTERIÄ tai painiketta, jossa on suurennuslasin kuva. 
</p>

<p><a href="#alkuun">Sivun alkuun</a></p>
</section>

<section>
<h2 id="hakuohje-tulokset">Perushaun tulokset</h2>

<p>
Perushaku avaa sana-artikkelin, joka on hakutuloksissa ensimmäisenä. 
</p>
<p>
Esimerkiksi haku ”maa” tuottaa tulokseksi <em>maa</em>-sana-artikkelin ja aakkostetun listan sanoista, 
jotka alkavat merkkijonolla ”<em>maa</em>”. Tällaisten sanojen määrän näet listan alussa. Listan sanat toimivat linkkeinä sana-artikkeleihin.
</p>
<p>
Jos sitä sanaa, jota etsit, ei ole sanakirjassa hakusanana, saat ilmoituksen sanan puuttumisesta.
</p>

<p><a href="#alkuun">Sivun alkuun</a></p>
</section>

<section>
<h2 id="hakuohje-hakusanalista">Hakusanalista</h2>

<p>
Sanoja voi hakea myös selaamalla hakusanalistaa. Perushakukentän (”kirjoita hakusana”) alla on aakkosellinen hakusanalista. Hakusanalistan voi sulkea ja avata uudestaan. Hakusanalistassa pääsee siirtymään hakusanojen alkukirjaimesta toiseen listan yläpuolella olevien kirjainpainikkeiden avulla. 
</p>
<p>
Hakusanalistalla kukin hakusana toimii linkkinä kyseiseen sana-artikkeliin. 
</p>
<p>
Varsinaisten eli päähakusanojen lisäksi listassa näkyvät viitehakusanat, jotka on merkitty hakusanalistaan nuolella (→). Viitehakusanoja eli viiteartikkeleiden hakusanoja ovat esimerkiksi päähakusanojen vanhat kirjoitusasut. Viiteartikkelit mahdollistavat hakujen onnistumisen myös muilla kuin päähakusanoilla. Viiteartikkelista on linkki pääartikkeliin, jossa esitetään sanaa koskeva tieto.
</p>
<p>
Hakusanalista ei näy etusivulla eikä haun jälkeen näkyviin tulevalla hakutulossivulla. 
Hakutulossivulla näkyy hakusanalistan asemesta hakutuloslista eli lista hakua vastaavista hakusanoista.
Myös hakutuloslistan pystyy avaamaan ja sulkemaan. Muutenkin hakutuloslista toimii samalla tavalla kuin hakusanalista. 
Hakusanalistan saa näkyviin millä tahansa sivulla SUURENNUSLASI + HAKU -kuvakkeesta. 
</p>

<p><a href="#alkuun">Sivun alkuun</a></p>
</section>

<section>
<h2 id="hakuohje-tarkennettu">Tarkennettu haku</h2>


<p><a href="#alkuun">Sivun alkuun</a></p>
</section>


</article>

</main>