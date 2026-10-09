// JavaScript source code

// Pikahaun ehdotusten hakeminen
function ajaxhaku(hakujono, callback) {

	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			callback(xhttp.responseText);
		}
	}
	xhttp.open("GET", "ajax.php?m=qs-ajax-results&query=" + encodeURIComponent(hakujono), true);
	xhttp.send();
}
function ajaxLahdekoodihaku(hakujono, callback) {

	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			callback(xhttp.responseText);
		}
	}
	xhttp.open("GET", "ajax.php?m=search-sources&query=" + encodeURIComponent(hakujono), true);
	xhttp.send();
}

// Alustetaan pikahaun ehdotukset
hakuehdotus(document.getElementById("hakuinput"), ajaxhaku);
hakuehdotus(document.getElementById("search-form-source"), ajaxLahdekoodihaku);

/*
function tyhjennaHakuinput(inputId) {

	document.getElementById(inputId).value = '';
	document.getElementById(inputId).focus();
	return false;
}
*/

async function haeUrl(ajaxUrl) {
	let myResponse = await fetch(ajaxUrl);
	let myValue = await myResponse.text();
	return myValue;
}

// Etusivun aakkosnappien linkkien alustaminen (kaikkien haku kerralla) (19.5.2026 JV)
async function aakkoslinkitAlustusKaikki(aakkoslinkitDivId) {
	aakkoslinkitDiv = document.getElementById(aakkoslinkitDivId);
	if (aakkoslinkitDiv) {
		var aakkoskirjaimet = aakkoslinkitDiv.getElementsByTagName("a");
		var prefixes = [];
		for (var i = 0; i < aakkoskirjaimet.length; i++) {
			var abcId = aakkoskirjaimet[i].id;
			prefixes[i] = abcId.split("prefix_")[1];		
		}
		var prefix_str = prefixes.join(",");
		var records = null;
		if (typeof(Storage) !== "undefined") {
			var records_str = sessionStorage.getItem("records_str");
			if (records_str) {
				records = JSON.parse(sessionStorage.getItem("records_str"));
			}
		}
		if (!records) {
			try {
				var jsonResult = await haeUrl("ajax.php?m=hakusanat-prefix-first-all&prefix=" + encodeURIComponent(prefix_str));
				records = JSON.parse(jsonResult);
				if (typeof(Storage) !== "undefined") {
					var records_str = JSON.stringify(records);
					sessionStorage.setItem("records_str", records_str);
				}
			}
			catch (error) {
			}
		}
		for (var i = 0; i < aakkoskirjaimet.length; i++) {
			var record = records.record[i];
			var uusiUrl = "?p=article&ks_id=" + record.ks_id + "&word=" + record.hakusana + "&list_id=" + record.id;
			aakkoskirjaimet[i].setAttribute("href", uusiUrl);
			//aakkoskirjaimet[i].style.color = "red"; // DEBUG
		}
	}
}

// Alustetaan aakkosnapit etusivulla ja sivupalkissa aakkoslinkit_div-id:llä (19.5.2026, 25.5.2026 JV)
aakkoslinkitAlustusKaikki("aakkoslinkit_div");
