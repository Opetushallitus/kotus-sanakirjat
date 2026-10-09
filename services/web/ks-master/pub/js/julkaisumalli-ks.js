// JavaScript source code
/*
Funktiot:
- navimenu
- hakuehdotus
- piilodiv
*/



/*
NAVIMENU

Handles opening and closing of navigation menus and disables focus on other parts of page while the list is open.
Huolehtii navigointivalikkojen avaamisesta ja sulkemisesta sekä estää fokuksen siirtymisen muualle sivulla.

Valikko on div-tyyppisen elementin sisällä. Sen id annetaan funktiolle parametrinä.
! Funktio olettaa, että valikon avaavan napin/input-elementin id on valikkodivin id + "_open".
! Samoin funktio olettaa, että valikkodivin sulkevan napin id on valikkodivin id + "_close".

*/
function navimenu(div_id) {
	var divelem = document.getElementById(div_id);
	if (divelem) {
		var openelem = document.getElementById(div_id + "_open");
		var closeelem = document.getElementById(div_id + "_close");
	
		/* lisätään overlay-elementti */
		if (document.getElementById("navimenu_overlay")) {
			// elementti on jo olemassa, tarviiko tehdä jotain?
		}
		else {
			var overlay = document.createElement('div');
			overlay.id = "navimenu_overlay"; 
			overlay.style.cssText = 'display: none; position: fixed;  z-index: 99; top: 0px; bottom: 0px; right: 0px; left: 0px; background-color: rgba(0, 127, 170, 0.5);';
			document.body.appendChild(overlay);
			overlay.onclick = function () {
				closeIt();
			};
		}

		/*var focuselems = [];*/
		divelem.style.display = "none";
		/* lisätään div-elementille tabindex-attribuutti, jotta se saa fokuksen */
		divelem.setAttribute("tabindex", "0");
		/* lisätään div-elementille aria-modal- ja role-attribuutit, jotta ruudunlukija
		   ei eksy lukemaan muuta taustalta */
		divelem.setAttribute("aria-modal", "true");
		divelem.setAttribute("role", "dialog");
		/* haetaan viimeinen fokusoitava elementti */
		var focusableEls = divelem.querySelectorAll('a[href]:not([disabled]), button:not([disabled]), textarea:not([disabled]), input[type="text"]:not([disabled]), input[type="radio"]:not([disabled]), input[type="checkbox"]:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])');
		var lastFocusableEl = focusableEls[focusableEls.length - 1];

		/* sulkeminen myös ESC-napilla sekä kuunnellaan yritetäänkö siirtää fokusta shifttäbillä alusta loppuun */
		divelem.addEventListener("keydown", function(e) {
			if (e.key == "Escape") {
				closeIt();
			}
			if (e.key === 'Tab') {
				if ( e.shiftKey ) /* shift + tab */ {
					if (document.activeElement === divelem) {
						lastFocusableEl.focus();
						e.preventDefault();
					}
				}
			}
		});
		/* kuunnellaan kun fokus vaihtuu ja siirretään se takaisin
			modaalin sisälle */
		document.addEventListener("focusin", function(e) {
			//console.log(div_id);
			if (!divelem.contains(e.target)) {
				//closeelem.focus();
				divelem.focus();
			}
		});

		openelem.onclick = function () {
			var overlay = document.getElementById("navimenu_overlay");
			// jos valikko ei ole näkyvissä, pannaan näkyviin ja overlay myös
			if (divelem.style.display === "none") {
				divelem.style.display = "block";
				overlay.style.display = "block";
				// focus oletuksena valikon div-elementille
				divelem.focus();
			}
			// [tullaanko tähän ikinä?] jos valikko on näkyvissä, piilotetaan se ja overlay myös
			else {
				divelem.style.display = "none";
				overlay.style.display = "none";
			}
		};
		closeelem.onclick = function () {
			closeIt();
		};
	}
	function closeIt() {
		var overlay = document.getElementById("navimenu_overlay");
		divelem.style.display = "none";
		overlay.style.display = "none";
	}
}


/*	HAKUEHDOTUS funktio perustuu W3school-sivuston esimerkkiin 
	https://www.w3schools.com/howto/howto_js_autocomplete.asp

	F U N K T I O N   K U T S U 
	
	Hakuehdotus-funktiota kutsutaan kerran sivun latautumisen jälkeen. Funktio
	vaatii parametreikseen tekstikentän eli INPUT-elementin (esimerkissä
	"myInput") javascript-objektina ja käyttäjän oman, ehdotukset tietokannasta
	hakevan, JSON-muotoisen taulukkomuuttujan palauttavan FUNKTION nimen
	("ajaxsearch"):
		hakuehdotus(document.getElementById("myInput"), ajaxsearch);

	OMAA FUNKTIOTA ("ajaxsearch"), joka tuottaa ehdotukset, kutsutaan
	hakuehdotus-funktion sisältä, kun ehdotuksia pitää hakea. Funktiolla pitää
	olla kaksi parametria:
		1. hakumerkkijono ("myInput"-elementin senhetkinen arvo), ja
		2. callback-funktio (jota kutsutaan, kun JSON-muotoinen tulos on valmis),
	esimerkiksi:
		function ajaxsearch(searchString, callback) {
			var xhttp = new XMLHttpRequest();
			xhttp.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					callback(xhttp.responseText);
				}
			}
			xhttp.open("GET", "ajax.php?find=" + encodeURIComponent(searchString), true);
			xhttp.send();
		}
	
	JSON-tuloksen pitää olla joko JSON:in "dictionary" -muodossa (kaksi nimi-
	arvoparia "label" ja "value"):
		{"record":[{"label":"fasaani", "value":"fasaani"}, ...]}
	tai yksinkertaisena JSON-taulukkona:
		["apple","orange","cat","dog"]
	Käytettäessä arvopareja labelin arvo näytetään ehdotuslistassa ja valuen
	arvo viedään tekstikenttään, jos se valitaan.


	H T M L - K O O D I

	Lomakkeen FORM-elementin autocomplete-attribuutin arvon pitää olla "off".
	INPUT-elementillä täytyy olla VANHEMPI (esim. div), jolla on attribuutit
	class="hakuehdotus" ja aria-live="polite". Funktion tarvitsemat tyylit on
	määritelty julkaisumallin CSS-tyylitiedostossa. Koodiesimerkki:
		<form autocomplete="off" action="/action_page.php" id="theform">
			<div class="autocomplete" aria-live="polite>
				<input id="myInput" type="text" name="myCountry" placeholder="Country"><input type="reset"><input type="submit">
			</div>
		</form>

	CSS-tyyliesimerkki (valmiina julkaisumallin tyylitiedostossa)
		[the container must be positioned relative:]
		.hakuehdotus {
			position: relative;
			display: inline-block;
		}
		input[type=reset], input[type=submit] {
			cursor: pointer;
		}
		.hakuehdotus-lista {
			position: absolute;
			border: 1px solid #d4d4d4;
			border-bottom: none;
			border-top: none;
			z-index: 99;
			[position the autocomplete items to be the same width as the container:]
			top: 100%;
			left: 0;
			right: 0;
		}
		.hakuehdotus-lista div {
			padding: 10px;
			cursor: pointer;
			background-color: #fff; 
			border-bottom: 1px solid #d4d4d4; 
		}
		[when navigating through the items using the arrow keys:]
		.hakuehdotus-aktiivinen {
			background-color: DodgerBlue !important; 
			color: #ffffff; 
		}

*/

function hakuehdotus(inp, sourceFunction) {
	/*
	Funktiokutsussa on kaksi parametriä: tekstikenttä-elementti tai ympäröivä
	div-tyyppinen elementti, jossa tekstikenttä sijaitsee, ja käyttäjän oman
	funktion nimi. Käyttäjän oma funktio palauttaa ehdotuslistassa näytettävät
	arvoparit, jotka on nimetty tunnuksilla "label" ja "value". Label
	näytetään ehdotuslistassa, value sijoitetaan tekstikenttään. Ks. ohjeet
	edellä.
	*/
	
	if (!inp) { return false; } // jos inp = null etc. niin exittäydytään
	
	var currentFocus;
	var origInputValue = "";
	/* lisätään kuuntelija reset-nappia varten */
	var form = inp.closest("form"); // ei toim IE:ssä, mutta ei väliäkään?
	if (form) {
		form.addEventListener('reset', function(e) {
			if (this.tagName == "input") {
				this.focus();
			}
			else {
				this.getElementsByTagName('input')[0].focus();
			}
		});
	}
	/* lisätään kuuntelija, kun fokus siirtyy hakuruutuun */
	inp.addEventListener("focus", function(e) {
		// valitaan koko tekstikentän sisältö
		inp.select();
	});
	/* funktio, joka ajetaan, kun tekstikenttään kirjoitetaan */
	inp.addEventListener("input", function(e) {
		var val = this.value;
		var thiselem = this;
		/* suljetaan mahd. auki olevat hakuehdotus-listat */
		closeAllLists();
		if (!val) { return false;}
		currentFocus = -1;
		/* kutsutaan käyttäjän omaa funktiota ja annetaan sille parametriksi
		tekstikentän arvo ja callback-funktio */
		sourceFunction(val,	function(jsonArr) {
			var a, b, i;
			var divId = thiselem.id + "hakuehdotus-list";
			/* estetään hakuehdotuslistan tuplaantuminen */
			if (document.getElementById(divId)) {
				document.getElementById(divId).remove(); 
			}
			/* luodaan DIV-elementti hakuehdotukslistaa varten */
			listdiv = document.createElement("div");
			listdiv.setAttribute("id", divId);
			listdiv.setAttribute("class", "hakuehdotus-lista");
			listdiv.setAttribute("aria-controls", thiselem.id);
			/* luodaan lista-elementti hakuehdotuksia varten */
			a = document.createElement("ul");
			/* noudetaan JSON-tulokset */
			var arr = JSON.parse(jsonArr);
			var entries;
			/* jos kyseessä on dictionary-tyyppinen, objektimuotoinen tulos */
			if (arr.record) {
        		var record = arr.record;
        		entries = Object.entries(record);
			}
			/* jos kyseessä yksinkertainen taulukko */
			else if (Array.isArray(arr)) {
        		var newArr = [];
				for (var i = 0; i < arr.length; ++i) {
					newArr.push({value:arr[i]});
				}
				entries = Object.entries(newArr);
			}
			if (entries.length > 0) {
				/* lisätään listadiv sivulle */
				thiselem.parentNode.appendChild(listdiv);
				/* lisätään visualisesti ruudun ulkopuolinen p-elementti ruudunlukijoille tarkoitettua ilmoitusta varten */
				p = document.createElement("p");
				p.classList.add("hakuehdotus-piilotettu");
				p.setAttribute("role", "alert");
				p.innerHTML = "Ehdotuksia " + entries.length + ", k&auml;yt&auml; nuolin&auml;pp&auml;imi&auml; selaukseen.";
				listdiv.appendChild(p);
				/* lisätään ul-lista sivulle */
				listdiv.appendChild(a);
				//for (var [key, obj] of entries) {  ei toimi IE:ssä koska EcmaScript6-ominaisuus
				for (var i = 0; i < entries.length; i++) {
					var key = entries[i][0];
					var obj = entries[i][1];
        			var label, value;
 					if (obj.label) {
            			label = obj.label;
					}
					else {
            			label = obj[Object.keys(obj)[0]];
					}
					if (obj.value) {
            			value = obj.value;
					}
					else {
            			value = obj[Object.keys(obj)[0]];
					}
					/* luodaan LI-elementti yksittäisille hakuehdotuksille */
					b = document.createElement("li");
					//b.setAttribute("tabindex", "-1");
					//b.innerHTML = arr[i];
					b.innerHTML = label;
					/* luodaan piilotettu kenttä, johon hakuehdotuksen value tallennetaan */
					//b.innerHTML += "<input type='hidden' value='" + arr[i] + "'>";
					b.innerHTML += "<input type='hidden' value='" + value.replace(/"/g, '&quot;').replace(/'/g, '&apos;') + "'>";
					/* luodaan kuuntelija tunnistamaan kun ehdotusta klikataan */
					b.addEventListener("click", function(e) {
						/* kopioidaan hakuehdotus tekstikenttään */
						inp.value = this.getElementsByTagName("input")[0].value;
						/* sulje hakuehdotuslista */
						closeAllLists();
						/* siirretään focus tekstikenttään */
						inp.focus();
						/* lähetetään lomake */
						inp.form.submit();
					});
					/* lisätään onmouseover-tapahtumakuuntelija hakuehdotusta varten */
					b.onmouseover = function(evt) {
						thisDiv = evt.target;
						var parent = thisDiv.parentElement;
						var childNodes = parent.getElementsByTagName("li");
						var pos = -1;
						var count = childNodes.length;
						for (var i = 0; i < count; ++i) {
							if (thisDiv === childNodes[i]) {
								pos = i;
								break;
							}
						}
						currentFocus = pos;
						addActive(childNodes);
					};
					a.appendChild(b);
				}
				origInputValue = val;
			}
		});
	});
	/* tapahtumakuuntelija näppäinpainalluksia varten */
	inp.addEventListener("keydown", function(e) {
		var x = document.getElementById(this.id + "hakuehdotus-list");
		if (x) x = x.getElementsByTagName("li");
		if (e.key == "ArrowDown") {
			/* alas-nuolen (arrow DOWN) painallus */
			currentFocus++;
			/* korostetaan valittu hakuehdotus */
			addActive(x);
			/* kopioidaan arvo tekstikenttään (erityisesti ruudunlukijoiden takia) */
			inp.value = x[currentFocus].getElementsByTagName("input")[0].value;
		}
		else if (e.key == "ArrowUp") { //up
			/* ylös-nuolen (arrow UP) painallus */
			currentFocus--;
			/* korostetaan valittu hakuehdotus */
			addActive(x);
			/* kopioidaan arvo tekstikenttään (erityisesti ruudunlukijoiden takia) */
			inp.value = x[currentFocus].getElementsByTagName("input")[0].value;
		}
		else if (e.key == "Escape") { //ESC
			/* ESC, suljetaan hakuehdotuslista ja palautetaan tekstikenttä entiselleen */
			// currentFocus = -1; unnecessary because it's already called in closeAllLists function
			removeActive(x);
			closeAllLists();
			inp.value = origInputValue;
		}
		else if (e.key == "Enter") {
			/* ENTER, estetään normaalitoiminta eli lomakkeen lähetys */
			//e.preventDefault();
			if (currentFocus > -1) {
				/* simuloidaan aktiivisen hakuehdotuksen klikkaaminen */
				if (x) x[currentFocus].click();
				currentFocus = -1;
			}
			else {
				//console.log(this.toString());
				this.form.submit();
			}
		}
	});
	/* tapahtumakuuntelija näppäinpainalluksen päättymistä varten */
	inp.addEventListener("keyup", function(e) {
		if (e.key == "ArrowUp") {
			// Kursoriongelman ratkaisuksi toimiva mutta kömpelö ja typerä:
			inp.selectionEnd = inp.value.length;
			inp.selectionStart = inp.selectionEnd;
		}
	});

	function addActive(x) {
		/* aktiivisen funktion korostus */
		if (!x) return false;
		/* poistetaan aktiivinen-luokka kaikista: */
		removeActive(x);
		if (currentFocus >= x.length) currentFocus = (x.length - 1);
		if (currentFocus < 0) currentFocus = 0;
		/* lisätään class-attribuutti "hakuehdotus-aktiivinen" */
		x[currentFocus].classList.add("hakuehdotus-aktiivinen");
		//x[currentFocus].setAttribute("role", "alert");
	}

	function removeActive(x) {
		/* aktiivinen-luokan poisto kaikista hakuehdotuksista */
		for (var i = 0; i < x.length; i++) {
			x[i].classList.remove("hakuehdotus-aktiivinen");
			//x[i].removeAttribute("role");
		}
	}
	function closeAllLists(elmnt) {
		currentFocus = -1;
		/* suljetaan kaikki hakuehdotuslistat, paitsi parametrina annettu */
		var x = document.getElementsByClassName("hakuehdotus-lista");
		for (var i = 0; i < x.length; i++) {
			if (elmnt != x[i] && elmnt != inp) {
				x[i].parentNode.removeChild(x[i]);
			}
		}
		//inp.focus();
	}
	/* kuuntelija klikkauksia varten */
	document.addEventListener("click", function (e) {
		closeAllLists(e.target);
	});
}


/* 
RESET-KUUNTELIJA

Lisää annetulle form-elementille reset-kuuntelijan, joka vie resetoitaessa fokuksen lomakkeen
ensimmäiseen input-elementtiin. Parametrinä annetaan form-elementti.
*/
function resetkuuntelija(form) {
	if (form) {
		form.addEventListener('reset', function(e) {
			if (this.tagName == "input") {
				this.focus();
			}
			else {
				this.getElementsByTagName('input')[0].focus();
			}
		});
	}
}



/*
PIILODIV

Avaa/sulje-napin ja siihen liittyvän piilotettavan/näytettävän div-tyyppisen elementin käsittely.
Parametreina annetaan avaa/sulje-napin ja piilotettavan div-tyyppisen (div, section jne.) elementin id:t.  
*/
/*
function piilodiv(avaanappi_id, div_id, avaa_txt = "Avaa", sulje_txt = "Sulje") {
	var avaanappi = document.getElementById(avaanappi_id);
	var div = document.getElementById(div_id);
	if (typeof(Storage) !== "undefined") {
		if (sessionStorage.getItem(div_id)) {
			var divDisplay = sessionStorage.getItem(div_id);
			if (divDisplay == "block") {
				avaa();
			}
			else {
				sulje();			
			}
		}
	}

	
	avaanappi.onclick = function () {
		if (getComputedStyle(div, null).display == "block") {
			sulje();
		}
		else {
			avaa();
		}
		// Tallennetaan valittu tila Web Storageen, jos sellainen saatavilla
		if (typeof(Storage) !== "undefined") {
			sessionStorage.setItem(div_id, div.style.display);
		}
	};
	function avaa() {
		if (div && avaanappi) {
			div.style.display = "block";
			avaanappi.style.backgroundImage = "url('/img/avaa_ylos_levea.png')";
			avaanappi.value = sulje_txt;
			avaanappi.innerHTML = sulje_txt;
			avaanappi.setAttribute("aria-expanded", "true");
		}
	}
	function sulje() {
		if (div && avaanappi) {
			div.style.display = "none";
			avaanappi.style.backgroundImage = "url('/img/avaa_alas_levea.png')";
			avaanappi.value = avaa_txt;
			avaanappi.innerHTML = avaa_txt;
			avaanappi.setAttribute("aria-expanded", "false");
		}
	}
}
*/

/*
PIILODETAILS (PIILOTA MOBIILISSA)

Poistetaan open-attribuutti details-elementistä (sivun latauksen alussa), jos ollaan kapeassa
näytässä tai halutaan piilottaa leveässäkin näytössä (kwic-tulokset).
*/
function piilodetails(detailsId, openInWideScreen = false) {
	// avataan leveässä ikkunassa
	if (window.innerWidth > 800 && openInWideScreen == false) {
		var details = document.getElementById(detailsId);
		if (details) {
			// tarkistetaan vielä, että kyseessä on oikea details-elementti
			if (details.tagName.toLowerCase() === 'details') {
				details.open = true;
			}
		}
	}
}