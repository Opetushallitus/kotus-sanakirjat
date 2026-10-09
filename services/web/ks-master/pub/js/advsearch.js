/* 
	LOMAKE-KUUNTELIJAT
	Lis�� annetulle lomakkeelle (form-elementti) kuuntelijoita, 
	jotka pit�v�t huolen ettei hakuun tule ristiriitaisia valintoja.
	14.8.2020 OL
*/
function lomakekuuntelija(form) {
    if (form) {

		// Resetoitaessa fokus siirret��n lomakkeen ensimm�iseen input-elementtiin.
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

		// Kokotekstihakua k�ytett�ess� disabloidaan muut kent�t
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
$(function() {

	// Disable selection of checkboxs' text labels.
	$('label').disableSelection();

});
*/