<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * cfg/Config.php
 * 
 * Configuration settings
 * KS uses an extended subclass to handle database connections
 * 
 * Copyright: 	Institute for the Languages of Finland
 * Author: 	Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * 
*/

$cfg['KS'] = array(
	// Base path for the application.
	'BASE_PATH' 	=> dirname(dirname(__FILE__)),

	// URL
	'KS_PATH'		=> "https://kaino-testi.kotus.valtion.fi/ks/",
	//'KS_URL'		=> "https://pilviosoite-tähän",
	'KS_URL'		=> "https://kielitoimistonsanakirja.fi/",
	//'KS_PATH'		=> "/",

	// MAILTO
	'MAILTO'		=> "mailto:kielitoimistonsanakirja@kotus.fi?subject=Palautetta Kielitoimiston sanakirjasta: ",

	// Palautelomakkeen osoite
	'FEEDBACK_URL'	=> "https://www.kotus.fi/kotus/yhteystiedot/yhteydenottolomakkeet/",

	// UKK-URL
	'UKK_URL'		=> "https://www.kotus.fi/sanakirjat/usein_kysyttyja_kysymyksia",

	// Directory separator.
	'DIR_SEP'		=> DIRECTORY_SEPARATOR,
	
	'LAST_UPDATE'	=> "28.9.2026"

);

/* kaikkien käyttöalojen sanoista */
$cfg['USAGE_SCOPES'] = array(
	""			=> "mikä tahansa",
	"alat"		=> "alatyylissä, alatyylisesti",
	"amm"		=> "ammatinharjoittajannimi, ammattinimike",
	"anat"		=> "anatomia",
	"antrop"	=> "antropologia",
	"ark"		=> "arkikielessä, arkikielinen",
	"arkeol"	=> "arkeologia",
	"astrol"	=> "astrologia",
	"biol"		=> "biologia",
	"el"		=> "eläintiede",
	"eläinlääk"	=> "eläinlääketiede",
	"farm"		=> "farmakologia, farmasia",
	"fil"		=> "filosofia",
	"filat"		=> "filatelia",
	"fon"		=> "fonetiikka",
	"fys"		=> "fysiikka",
	"fysiol"	=> "fysiologia",
	"geol"		=> "geologia",
	"geom"		=> "geometria",
	"halv"		=> "halventava, halventavasti",
	"harv"		=> "harvinainen, harvoin",
	"hist"		=> "historia, historian tutkimus",
	"ilm"		=> "ilmailu",
	"ilmat"		=> "ilmatiede",
	"iron"		=> "ironinen, ironisesti",
	"jalok"		=> "jalokiviala",
	"kal"		=> "kalastus",
	"kansat"	=> "kansatiede",
	"kasv"		=> "kasvitiede",
	"kem"		=> "kemia",
	"kiel"		=> "kielitiede, kielioppi",
	"kirjap"	=> "kirjapainoala",
	"korttip"	=> "korttipelit",
	"kosmet"	=> "kosmetiikka",
	"koul.slg"	=> "koululaisslangissa",
	"kuv"		=> "kuvakielessä, kuvallisesti, kielikuva",
	"kuvat"		=> "kuvataiteet",
	"käs"		=> "käsityöt",
	"last"		=> "lastenkielessä, lapsille puhuttavassa kielessä",
	"leik"		=> "leikillinen, leikillisesti",
	"log"		=> "logiikka",
	"lyh"		=> "lyhenne",
	"lääk"		=> "lääketiede",
	"maal"		=> "maalausala",
	"maanm"		=> "maanmittaus",
	"maant"		=> "maantiede",
	"maat"		=> "maatalous",
	"mat"		=> "matematiikka",
	"mer"		=> "merenkulku",
	"met"		=> "metalliala",
	"mets"		=> "metsästys",
	"metsät"	=> "metsätalous, metsätiede",
	"miner"		=> "mineralogia",
	"murt"		=> "murteissa, murteellinen",
	"mus"		=> "musiikki",
	"myt"		=> "mytologia",
	"oik"		=> "oikeustiede, oikeusala",
	"opt"		=> "optiikka",
	"paleont"	=> "paleontologia",
	"pap"		=> "paperi- ja selluteollisuus",
	"pol"		=> "politiikka, poliittinen",
	"psyk"		=> "psykologia",
	"puut"		=> "puuteollisuus",
	"raam"		=> "Raamatussa, Raamatun kielessä",
	"rak"		=> "rakennusala, rakennustaide",
	"rautat"	=> "rautatieala",
	"run"		=> "runousopissa",
	"runok"		=> "runokielessä",
	"ruok"		=> "ruokatalous, ruoka-ala",
	"slg"		=> "slangissa",
	"sos"		=> "sosiaalinen, sosiaali-",
	"sosiol"	=> "sosiologia",
	"sot"		=> "sotilaskielessä, sotilasala",
	"sot.slg"	=> "sotilasslangissa",
	"sähk"		=> "sähköala, sähkötekniikka",
	"taidehist"	=> "taidehistoria",
	"tal"		=> "taloustiede, talouselämä",
	"tekn"		=> "tekniikka",
	"tekst"		=> "tekstiiliala",
	"teletekn"	=> "teletekniikka",
	"teol"		=> "teologia",
	"tietotekn"	=> "tietotekniikka",
	"tilastot"	=> "tilastotiede",
	"täht"		=> "tähtitiede",
	"urh"		=> "urheilu",
	"usk"		=> "uskonto, uskonnollisessa kielenkäytössä",
	"valok"		=> "valokuvaus",
	"vanh"		=> "vanhentunut",
	"yleisk"	=> "yleiskielessä",
	"ylät"		=> "ylätyylissä, ylätyylisesti"
);

/* kaikki */
$cfg['WORD_GROUPS'] = array(
	""				=> "mikä tahansa", 
	"adjektiivi" 	=> "adjektiivi",
	"adverbi" 		=> "adverbi",
	"alistuskonjunktio" => "alistuskonjunktio",
	"interjektio" 	=> "interjektio",
	"järjestysluku" => "järjestysluku",
	"kieltoverbi" 	=> "kieltoverbi",
	"konjunktio" 	=> "konjunktio",
	"numeraali" 	=> "numeraali",
	"postpositio" 	=> "postpositio",
	"prepositio" 	=> "prepositio",
	"pronomini" 	=> "pronomini",
	"rinnastuskonjunktio" => "rinnastuskonjunktio",
	"substantiivi" 	=> "substantiivi",
	"verbi" 		=> "verbi"
);

$cfg['TINDEX'] = array(
	""		=> "mikä tahansa",
	"1" 	=> "1",
	"2" 	=> "2",
	"3" 	=> "3",
	"4" 	=> "4",
	"5" 	=> "5",
	"6" 	=> "6",
	"7" 	=> "7",
	"8" 	=> "8",
	"9" 	=> "9",
	"10" 	=> "10",
	"11" 	=> "11",
	"12" 	=> "12",
	"13" 	=> "13",
	"14" 	=> "14",
	"15" 	=> "15",
	"16" 	=> "16",
	"17" 	=> "17",
	"18" 	=> "18",
	"19" 	=> "19",
	"20" 	=> "20",
	"21" 	=> "21",
	"22" 	=> "22",
	"23" 	=> "23",
	"24" 	=> "24",
	"25" 	=> "25",
	"26" 	=> "26",
	"27" 	=> "27",
	"28" 	=> "28",
	"29" 	=> "29",
	"30" 	=> "30",
	"31" 	=> "31",
	"32" 	=> "32",
	"33" 	=> "33",
	"34" 	=> "34",
	"35" 	=> "35",
	"36" 	=> "36",
	"37" 	=> "37",
	"38" 	=> "38",
	"39" 	=> "39",
	"40" 	=> "40",
	"41" 	=> "41",
	"42" 	=> "42",
	"43" 	=> "43",
	"44" 	=> "44",
	"45" 	=> "45",
	"46" 	=> "46",
	"47" 	=> "47",
	"48" 	=> "48",
	"49" 	=> "49",
	"50" 	=> "50",
	"51" 	=> "51",
	"52" 	=> "52",
	"53" 	=> "53",
	"54" 	=> "54",
	"55" 	=> "55",
	"56" 	=> "56",
	"57" 	=> "57",
	"58" 	=> "58",
	"59" 	=> "59",
	"60" 	=> "60",
	"61" 	=> "61",
	"62" 	=> "62",
	"63" 	=> "63",
	"64" 	=> "64",
	"65" 	=> "65",
	"66" 	=> "66",
	"67" 	=> "67",
	"68" 	=> "68",
	"69" 	=> "69",
	"70" 	=> "70",
	"71" 	=> "71",
	"72" 	=> "72",
	"73" 	=> "73",
	"74" 	=> "74",
	"75" 	=> "75",
	"76" 	=> "76",
	"77" 	=> "77",
	"78" 	=> "78"
);

$cfg['AVINDEX'] = array(
	""		=> "mikä tahansa",
	"a" 	=> "A",
	"b" 	=> "B",
	"c" 	=> "C",
	"d" 	=> "D",
	"e" 	=> "E",
	"f" 	=> "F",
	"g" 	=> "G",
	"h" 	=> "H",
	"i" 	=> "I",
	"j" 	=> "J",
	"k" 	=> "K",
	"l" 	=> "L",
	"m" 	=> "M"
);


?>
