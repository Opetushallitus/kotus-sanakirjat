// Details-elementin kuuntelija, joka skrollaa oikeaan kohtaan listaa, kun se avataan ensimmäisen kerran.
function detailsUpdate() {
	if (this.open) {
		var focusable = this.querySelector("a:not([tabindex='-1'])");
		if (focusable) {
			focusable.parentNode.parentNode.scrollTop = focusable.parentNode.offsetTop - focusable.parentNode.parentNode.offsetTop;
		}
	}
	// poistetaan tämä käsittelijä heti 1. käytön jälkeen
	this.removeEventListener('toggle', detailsUpdate);
}

var scrollAjax = true;

function haeViitekohdePrefix(prefix) {
	var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			var obj = JSON.parse(xhttp.responseText);
			console.log(obj);
            var parentElem = document.getElementById("hlista");
            parentElem.innerHTML = '';
            var id1 = obj.record[0].id;
            var size = obj.record.length;
            for (var i = 0; i < size; i++) {
				var item = document.createElement("div");
                item.setAttribute("id", obj.record[i].id);

				// koostetaan linkki artikkeliin
				var link = koostaHakusanaLinkki(obj.record[i], id1, false, false);

                item.appendChild(link);
                parentElem.appendChild(item);
            }
            var target = document.getElementById(id1);
            target.parentNode.scrollTop = target.offsetTop - target.parentNode.offsetTop;
         }
  	}
  	xhttp.open("GET", "ajax.php?m=hakusanat-prefix&prefix=" + encodeURIComponent(prefix), true);
  	xhttp.send();
}


// pakollinen parametri: list_id
// pikahaussa lisäksi: keyword
// tarkennetussa haussa lisäksi: existSession
// KWIC-haussa lisäksi: isKwic
function haeHakusanatListaan(list_id, keyword, existSession, isKwic = false) {
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			var obj = JSON.parse(xhttp.responseText);
			var parentElem = document.getElementById("hlista");
			parentElem.innerHTML = '';
			if (isKwic) { // 14.9.2026 JV sivutusnapit KWIC-haulle
				var sivutusnapitHtml = sivutusNapit(list_id, existSession);
				var kwicsivutus1 = document.getElementById("kwicsivutus1");
				var kwicsivutus2 = document.getElementById("kwicsivutus2");
				kwicsivutus1.innerHTML = sivutusnapitHtml; // napit ennen hakutuloksia
				kwicsivutus2.innerHTML = sivutusnapitHtml; // napit hakutulosten jälkeen
			}
			var size = obj.record.length;
			for (var i = 0; i < size; i++) {
				// KWIC-haku
				if (isKwic) {
					var item = document.createElement("div");
					item.setAttribute("id", obj.record[i].id);
					// koostetaan linkki artikkeliin
					var link = koostaHakusanaLinkki(obj.record[i], list_id, keyword, existSession, isKwic);
					// lisätään myös konteksti
					// hakutulos ensiksi div-elementin sisään
					item.innerHTML = obj.record[i].kwic;
					// linkki div-elementin sisään, jos ensimmäinen artikkelissa
					if (i == 0 || obj.record[i].ks_id != obj.record[i - 1].ks_id) {
						if (i + 1 < size && obj.record[i + 1].ks_id == obj.record[i].ks_id) {
							// ensimmäinen monikontekstinen hakutulos, lisätään rivinvaihto alkuun
							item.prepend(document.createElement("br"));
						}
						// lisätään hakusanalinkki alkuun
						item.prepend(link);
						item.setAttribute("class", "kwic kwiccontext kwicfirst");
					}
					else {
						item.setAttribute("class", "kwic kwiccontext kwicmore");
					}
				}
				else {
					// muut kuin kwic-tulokset
					var item = document.createElement("div");
					item.setAttribute("id", obj.record[i].id);
					// koostetaan linkki artikkeliin
					var link = koostaHakusanaLinkki(obj.record[i], list_id, keyword, existSession, isKwic);
					item.appendChild(link);
				}
				parentElem.appendChild(item);
			}
			var target = document.getElementById(list_id);
			if (target) {
				target.parentNode.scrollTop = target.offsetTop - target.parentNode.offsetTop;
			}
			
			// näppäimistön käsittely
			parentElem.addEventListener("keydown", function(e) {
				if (e.key == "ArrowDown") {
					if (this.contains(document.activeElement)) {
						var currenta = document.activeElement;
						if (currenta.parentElement.nextSibling && currenta.parentElement.nextSibling.firstElementChild) { // testataan näin perusteellisesti niin ei tule turhia virheilmoituksia
							var next = currenta.parentElement.nextSibling.firstElementChild;
							e.preventDefault();
							next.setAttribute("tabindex", "0");
							next.focus();
							currenta.setAttribute("tabindex", "-1");
						}
					}
				}
				else if (e.key == "ArrowUp") {
					if (this.contains(document.activeElement)) {					
						var currenta = document.activeElement;
						if (currenta.parentElement.previousSibling && currenta.parentElement.previousSibling.firstElementChild) {
							var previous = currenta.parentElement.previousSibling.firstElementChild;
							e.preventDefault();
							previous.setAttribute("tabindex", "0");
							previous.focus();
							currenta.setAttribute("tabindex", "-1");
						}
					}
				}
				else if (e.key == "PageDown") {
					if (this.contains(document.activeElement)) {
						var currenta = document.activeElement;
						var previous = currenta;
						var i;
						for (i = 0; i < 17; i++) {
							if (previous.parentElement.nextSibling && previous.parentElement.nextSibling.firstElementChild) {
								var next = previous.parentElement.nextSibling.firstElementChild;
								e.preventDefault();
								next.setAttribute("tabindex", "0");
								next.focus();
								previous.setAttribute("tabindex", "-1");
								previous = next;
							}
							else {
								break;
							}
						}
					}
				}
				else if (e.key == "PageUp") {
					if (this.contains(document.activeElement)) {					
						var currenta = document.activeElement;
						var next = currenta;
						var i;
						for (i = 0; i < 17; i++) {
						if (next.parentElement.previousSibling && next.parentElement.previousSibling.firstElementChild) {
							var previous = next.parentElement.previousSibling.firstElementChild;
								e.preventDefault();
								previous.setAttribute("tabindex", "0");
								previous.focus();
								next.setAttribute("tabindex", "-1");
								next = previous;
							}
							else {
								break;
							}
						}
					}
				}
			});
		}
	}
	// koostetaan ajax-kyselyn url
	ajaxurl = koostaAjaxHaku(list_id, keyword, existSession, isKwic);
	xhttp.open("GET", ajaxurl, true);
	xhttp.send();
}

function haeLisaa(keyword, list_id, up_or_down, existSession, isKwic = false) {
	if (scrollAjax) {
		var parentElem = document.getElementById("hlista");
		var inFocus = parentElem.contains(document.activeElement);
		var firstId = parseInt(parentElem.firstElementChild.getAttribute("id"));
		if (firstId <= 1 && up_or_down < 0) {
			return;
		}
		var lastId = parseInt(parentElem.lastElementChild.getAttribute("id"));
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			scrollAjax = false;
			if (this.readyState == 4 && this.status == 200) {
				//console.log(xhttp.responseText);
				var obj = JSON.parse(xhttp.responseText);
				var size = obj.record.length;
				if (parseInt(obj.record[0].id) < firstId || parseInt(obj.record[size - 1].id) > lastId) {
					// tyhjennetään koko lista
					while (parentElem.firstChild) {
						parentElem.removeChild(parentElem.firstChild);
					}
					for (var i = 0; i < size; i++) {
						var item = document.createElement("div");
						item.setAttribute("id", obj.record[i].id);
						// koostetaan linkki artikkeliin
						var link = koostaHakusanaLinkki(obj.record[i], list_id, keyword, existSession, isKwic);
						// KWIC-haku: lisätään myös konteksti
						if (isKwic) {
							// hakutulos ensiksi div-elementin sisään
							item.innerHTML = obj.record[i].kwic;
							// linkki div-elementin sisään, jos ensimmäinen artikkelissa
							if (i == 0 || obj.record[i].ks_id != obj.record[i - 1].ks_id) {
								if (i + 1 < size && obj.record[i + 1].ks_id == obj.record[i].ks_id) {
									// ensimmäinen monikontekstinen hakutulos, lisätään rivinvaihto alkuun
									item.prepend(document.createElement("br"));
								}
								// lisätään hakusanalinkki alkuun
								item.prepend(link);
								item.setAttribute("class", "kwic kwiccontext kwicfirst");
							}
							else {
								item.setAttribute("class", "kwic kwiccontext kwicmore");
							}
						}
						else {
							// muut kuin kwic-tulokset
							item.appendChild(link);
						}
						parentElem.appendChild(item);
					}
					var target;
					var focusable;
					if (up_or_down < 0) {
						target = document.getElementById(firstId);
						focusable = target;
					}
					else {
						target = document.getElementById(lastId - 17);
						focusable = document.getElementById(lastId);
						var newlastid = parseInt(lastId) + 1;
						if (document.getElementById(newlastid)) {
							focusable = document.getElementById(newlastid);
						}
					}
					target.parentNode.scrollTop = target.offsetTop - target.parentNode.offsetTop;
					focusable.firstElementChild.setAttribute("tabindex", "0");
					if (inFocus) {
						focusable.firstElementChild.focus();
					}
				}
				scrollAjax = true;
			}
		}
		// koostetaan ajax-kyselyn url
		if (up_or_down < 0) {
			ajaxurl = koostaAjaxHaku(firstId, keyword, existSession, isKwic);
		}
		else {
			ajaxurl = koostaAjaxHaku(lastId, keyword, existSession, isKwic);
		}
		xhttp.open("GET", ajaxurl, true);
		xhttp.send();
	}
}

function listup(keyword, list_id, existSession, isKwic = false) {
	haeLisaa(keyword, list_id, -1, existSession, isKwic);
}

function listdown(keyword, list_id, existSession, isKwic = false) {
	haeLisaa(keyword, list_id, 1, existSession, isKwic);
}


function skrollaa(elem, keyword, existSession, list_id, isKwic = false) {
	if (elem.scrollTop >= (elem.scrollHeight - elem.clientHeight))  {
		listdown(keyword, list_id, existSession, isKwic);
	}
	if (elem.scrollTop <= 0) {
		listup(keyword, list_id, existSession, isKwic);
	}
}

// sanakirjakohtainen hakusanan tietojen muotoilu
// ja hakutyyppikohtainen linkki parametreineen
function koostaHakusanaLinkki(record, list_id, keyword, hilight, iskwic = false) {
	// KS: hakusana + homonyyminro yläindeksinä + sanaluokka 
	// Viitehakusanan tyyppi: V
	var txt = record.hakusana;
	var word = record.hakusana;
	if (record.homonyyminro) {
		txt = txt + "<sup>" + record.homonyyminro + "</sup>";
		word = word + ":" + record.homonyyminro;
	}
	if (record.tyyppi == "V") {
		txt = txt + " <span>→</span>";
	}
	if (record.tyyppi == "viiteartikkeli") {
		txt = txt + " <span>→</span>";
	}

	// linkki artikkeliin
	// KS: ks_id
	var link = document.createElement("a");
	// sivupalkin mukaan muuttuvat attribuutit (hakusanat/pikahaku/tark.haku)
	// p=article/qs-article/as-article
	// &keyword vain pikahaussa
	// &hilight vain tarkennetussa haussa ja kwic-haussa
	// &iskwic ja matchNum vain kwic-haussa

	if (iskwic) { //KWIC-haku
		link.setAttribute("href", "?p=kwic-article&ks_id=" + encodeURIComponent(record.ks_id) + "&word=" + word + "&list_id=" + record.id + "&hilight");		
	} else if (hilight) { //tarkennettu haku
		link.setAttribute("href", "?p=as-article&ks_id=" + encodeURIComponent(record.ks_id) + "&word=" + word + "&list_id=" + record.id + "&hilight");		
	} else if (keyword) { //pikahaku
		link.setAttribute("href", "?p=qs-article&ks_id=" + encodeURIComponent(record.ks_id) + "&word=" + word + "&list_id=" + record.id + "&keyword=" + encodeURIComponent(keyword));
	} else { //hakusanalista
		link.setAttribute("href", "?p=article&ks_id=" + encodeURIComponent(record.ks_id) + "&word=" + word + "&list_id=" + record.id);		
	}

	let params = new URLSearchParams(document.location.search);
	let ks_id = params.get("ks_id");

	if (record.id == list_id && iskwic == false) {
		link.innerHTML = "<strong>" + txt + "</strong>";
	} else if (iskwic == true && ks_id && ks_id == record.ks_id) {
		link.innerHTML = "<strong>" + txt + "</strong>";
	} else {
		link.setAttribute("tabindex", "-1");
		link.innerHTML = txt;
	}


	/*
	// KWIC-haku: lisätään myös konteksti
	if (iskwic) {		
		var elem = document.createElement("span");
		elem.setAttribute("class", "kwiccontext");
		elem.innerHTML = record.kwic;
		elem.prepend(link);
		link = elem;	
	}
		*/
	
	return link;
}

// Ajax-kyselyn url, riippuen haun tyypistä
function koostaAjaxHaku(list_id, keyword, existSession, isKwic = false) {
	// sivupalkin mukaan muuttuvat parametrit
	// m=hakusanat/qs-results/as-results
	// &prefix vain pikahaussa
	// &existSession vain tarkennetussa haussa
	// &isKwic vain KWIC-haussa
	if (existSession) { //tarkennettu haku
		if (isKwic) {
			list_id = Math.floor(list_id / 10) * 10 + 1; // KWIC-haussa list_id on tulossivun 1. osumarivi
			txt = "ajax.php?m=kwic-results&list_id=" + encodeURIComponent(list_id) + 
				"&existSession=" + encodeURIComponent(existSession);
		} else {
			txt = "ajax.php?m=as-results&list_id=" + encodeURIComponent(list_id) + 
				"&existSession=" + encodeURIComponent(existSession);
		}
	} else if (keyword) { //pikahaku
		txt = "ajax.php?m=qs-results&prefix=" + encodeURIComponent(keyword) + 
			"&list_id=" + encodeURIComponent(list_id);
	} else {
		txt = "ajax.php?m=hakusanat&list_id=" + encodeURIComponent(list_id);
	}
	
	return txt;
}

function haeKwicSivu(list_id, keyword, existSession) {
	haeHakusanatListaan(list_id, keyword, existSession, true);
}


function sivutusNapit(list_id, existSession) {
	var html = "";

	//var lkm = parseInt(sessionStorage.getItem("hakutuloslkm"));
	var lkm = null;
	if (typeof kwicHakutulosLkm !== 'undefined' && kwicHakutulosLkm != null) {
		lkm = kwicHakutulosLkm;
	}
	var max = 10;
	var hakuhref = "";

	// esitellään apumuuttujia
	var sivunro = 0;
	var sivustart = 0;

	var sivulkm = Math.ceil(lkm / max);
	var laststart = (sivulkm - 1) * max + 1;
	
	var thissivu = Math.floor(list_id / max); // 0 = eka sivu
	var firstsivu = 0;
	var lastsivu = sivulkm - 1;

	var rangestart = thissivu - 2;
	if (rangestart < firstsivu) {
		rangestart = firstsivu;
	}

	var rangeend = thissivu + 2;
	if (rangeend > lastsivu) {
		rangeend = lastsivu;
	}

	var edsivu = thissivu - 1;
	var edsivu_html = "";
	if (edsivu >= firstsivu) {
		sivustart = edsivu * max + 1;
		edsivu_html = ' <a href="javascript:null" class="kwicsivutusnappi" onclick="';
		edsivu_html += teeSivutusNapitOnClick(sivustart, existSession);
		edsivu_html += '">Edellinen</a> ';
	}

	var seursivu = thissivu + 1;
	var seursivu_html = "";
	if (seursivu <= lastsivu) {
		sivustart = seursivu * max + 1;
		seursivu_html = ' <a href="javascript:null" class="kwicsivutusnappi" onclick="';
		seursivu_html += teeSivutusNapitOnClick(sivustart, existSession);
		seursivu_html += '">Seuraava</a> ';
	}

	var firststart = 1;
	var laststart = lastsivu * max + 1;
	
	// Näytetään sivulinkit vain jos sivulukumäärä on suurempi kuin yksi
	if (sivulkm > 1) {
		// ekasivu näytetään aina
		if (firstsivu < rangestart) {
			//html += ' <a href="tee_listaushref(1, hakuhref)" onclick="';
			html += ' <a href="javascript:null" class="kwicsivutusnappi" onclick="';
			html += teeSivutusNapitOnClick(firststart, existSession);
			html += '">1</a> ';
			if (firstsivu < rangestart - 1) {
				html += "&#8230; ";
			}
		}
	
		// kaksi aikaisempaa sivua
		for (var i = rangestart; i < thissivu; i++) {
			sivunro = i + 1;
			sivustart = i * max + 1;
			html += ' <a href="javascript:null" class="kwicsivutusnappi" onclick="';
			html += teeSivutusNapitOnClick(sivustart, existSession);
			html += '">' + sivunro + '</a> ';
		}

		// tääsivu
		sivunro = thissivu + 1;
		html += ' <b style="color:#c33d00;" class="kwicsivutusnappi">' + sivunro + '</b> ';

		// kaksi seuraavaa sivua
		for (var i = thissivu + 1; i <= rangeend; i++) {
			sivunro = i + 1;
			sivustart = i * max + 1;
			html += ' <a href="javascript:null" class="kwicsivutusnappi" onclick="';
			html += teeSivutusNapitOnClick(sivustart, existSession);
			html += '">' + sivunro + '</a> ';
		}
	
		// vikasivu näytetään aina
		if (lastsivu > rangeend) {
			if (rangeend + 1 < lastsivu) {
				html += "&#8230; "; //… TAI &hellip;
			}
			sivunro = lastsivu + 1;
			html += ' <a href="javascript:null" class="kwicsivutusnappi" onclick="';
			html += teeSivutusNapitOnClick(laststart, existSession);
			html += '">' + sivunro + '</a> ';
		}

		html = '<form method="get" action="?" onsubmit="return validate_form(this)"><p>' + edsivu_html + html + seursivu_html + '</p></form>';
		
	}
	return html;
}

function teeSivutusNapitOnClick(list_id, existSession) {
	var command = "haeKwicSivu(" + list_id + ", false, '" + existSession + "'); return false;";
	return command;
}
