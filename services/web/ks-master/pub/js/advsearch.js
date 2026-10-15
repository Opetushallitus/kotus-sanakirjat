/* 
	LOMAKE-KUUNTELIJAT
	Lisää annetulle lomakkeelle (form-elementti) kuuntelijoita, 
	jotka pitävät huolen ettei hakuun tule ristiriitaisia valintoja.
	14.8.2020 OL
*/
function lomakekuuntelija(form) {
    if (form) {

		// Resetoitaessa fokus siirretään lomakkeen ensimmäiseen input-elementtiin.
        form.addEventListener('reset', function(e) {

			var elements = ['search-form-keyword', 'search-form-from', 'search-form-word-group',
							'search-form-usage-scope', 'search-form-fulltext'];

			elements.forEach(function(item){
				document.getElementById(item).removeAttribute("disabled")
			})

            if (this.tagName == "input") {
                this.focus();
            }
            else {
                this.getElementsByTagName('input')[0].focus();
            }
        });

		// Kokotekstihakua käytettäessä disabloidaan muut kentät
		var fulltext = document.getElementById('search-form-fulltext');
		fulltext.addEventListener('input', (event) => {
			var elements = ['search-form-keyword', 'search-form-from', 'search-form-word-group',
							'search-form-usage-scope'];
			elements.forEach(function(item){
				if (fulltext.value != '') {
					document.getElementById(item).setAttribute("disabled", true);
				} else {
					document.getElementById(item).removeAttribute("disabled");
				}
			})
		});
    }
}

/*
	Kustomoitu validointi kokotekstihaulle
*/
document.addEventListener("submit", function(event) {
	
	if (event.target.id == "search-form2") {
		const field = document.getElementById("search-form-fulltext");
		const fieldError = document.getElementById("search-form-fulltext-error");
		const value = field.value.trim();
		// hakuehdon tulee sisältää vähintään yksi muu merkki kuin whitespace, * tai ?
		if (!/[^\*\?\s]/.test(value)) {
        	event.preventDefault();
			fieldError.textContent = "Hakuehdon tulee sisältää vähintään yksi muu merkki kuin * tai ?.";
			fieldError.style.display = "block";
			field.setAttribute("aria-invalid", "true");
			field.focus();
			return false;
		}
		fieldError.textContent = "";
		fieldError.style.display = "none";
		field.removeAttribute("aria-invalid");
	}
	else if (event.target.id == "search-form") {
		const fieldError = document.getElementById("search-form-error");
		// Ei haetakaan kaikkia lomakkeen input- ja select-kenttiä,
		//const fields = event.target.querySelectorAll("input, select");
		// muutetaan NodeList taulukoksi
		//const fieldsArray = Array.from(fields);
		// vain ne kentät, joita verrataan, että onko niissä arvoa
		const fieldsIdArray = ["search-form-keyword", "search-form-tindex", "search-form-avindex", "search-form-word-group", "search-form-usage-scope"];
		// tarkistetaan, että ainakin yksi kenttä on täytetty
		let isAnyFieldFilled = false;
		fieldsIdArray.forEach(function(fieldId) {
			const field = document.getElementById(fieldId);
			// tarkistetaan, että kenttä ei ole tyhjä ja että se ei ole "search-form-hwfrom"
			if (field.value.trim() !== "") {
				isAnyFieldFilled = true;
				//console.log(" ! field: " + field.id + " (value: " + field.value + ")");
			}
		});
		if (isAnyFieldFilled === false) {
			event.preventDefault();
			fieldError.textContent = "Täytä ainakin yksi hakukenttä.";
			fieldError.style.display = "block";
			return false;
		}
		else {
			fieldError.textContent = "";
			fieldError.style.display = "none";
		}
	}
});

// Add Event Listener to document
document.addEventListener("DOMContentLoaded", function () {
	const inputField = document.getElementById("search-form-fulltext");
	const checkbox = document.getElementById("search-form-lemma");
	const checkboxLabel = document.getElementById("search-form-lemma-label");
	inputField.addEventListener("input", (event) => {
		const value = inputField.value.trim();
		if (!/[^\*\?\s]/.test(value)) {
			inputField.setAttribute("aria-invalid", "true");
		}
		else {
			inputField.removeAttribute("aria-invalid");
		}
		if (/[\*\?]/.test(value)) {
			// if wildcards are used, disable search-form-lemma checkbox
			checkbox.setAttribute("disabled", true);
			checkboxLabel.style.setProperty("text-decoration", "line-through");
		}
		else {
			checkbox.removeAttribute("disabled");
			checkboxLabel.style.removeProperty("text-decoration");
		}
	});
});


/*
Kustomoitu validointi kokotekstihaulle

window.addEventListener("load", function() {
	var fulltextInput = document.getElementById("search-form-fulltext");
});
*/

/*
$(function() {

	// Disable selection of checkboxs' text labels.
	$('label').disableSelection();

});
*/