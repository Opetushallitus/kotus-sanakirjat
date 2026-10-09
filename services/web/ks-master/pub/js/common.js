
var qsInputDefValue;

$(function() {
	
	// Get input field default value when the document is scriptable.
	// Depends on the translations, thus needed to grab it via DOM.
	qsInputDefValue = $('#quick-search-input').val();

	// Bind event handlers for click and hover to the input field.
	$('#quick-search-input').bind('click blur', qsInputToggle).keypress(function(e) {
		// If enter pressed with a default value (Keyword...),
		// prevent default action and return false. This should
		// not happen on any circumstances, as click/blur on the
		// field *should* clear the default value.
		if (e.which == 13 && $(this).val() == qsInputDefValue) { 
			e.preventDefault();
			return false;
		}
	}).autocomplete({
		minLength: 2,
		source: function(request, response) {
			$.ajax({
				url: "ajax.php",
				dataType: "json",
				data: {
					m: "qs-ajax-results",
					type: 1,
					query: $('#quick-search-input').val()
				},
			success: function(data) {
				response($.map(data, function(item) {
					return {label: item.hakusana, value: item.hakusana}
				}));
			}});
		}
	});

	// Bind event handler for click to the search button.
	$('#quick-search-go').click(function(e) {
		// If button is clicked with a default value (Keyword...),
		// prevent default action and return false. This effectively
		// disabled submitting form with a field's default value
		// (ie. no keyword was set by user).
		if ($('#quick-search-input').val() == qsInputDefValue) { 
			e.preventDefault();
			return false;
		}
	});
		
});

/*
function ascii_to_hexa(str) {
	var arr1 = [];
	for (var n = 0, l = str.length; n < l; n ++) {
		var hex = Number(str.charCodeAt(n)).toString(16);
		arr1.push(hex);
	}
	return arr1.join('');
}
*/

function qsInputToggle() {
	var input = $('#quick-search-input');
	
	if ($(input).val() == qsInputDefValue) {
		$(input).val('').css('color', '#000000');
	} else if ($(input).val() == "") {
		$(input).val('').css('color', '#808080').val(qsInputDefValue);
	}
}

/* tarvitaanko näitäkään enää? / 7.2.2020 OL */
$.fn.extend({
	disableSelection : function() {
		this.each(function() {
			this.onselectstart = function() { return false; };
			this.unselectable = "on";
			$(this).css('user-select', 'none').css('-o-user-select', 'none')
			.css('-moz-user-select', 'none').css('-khtml-user-select', 'none')
			.css('-webkit-user-select', 'none');
		});
		return this;
	}
});

$.fn.fadeToggle = function(speed, easing, callback) { 
   return this.animate({opacity: 'toggle'}, speed, easing, callback); 
};

$.fn.highlight = function(b){function a(e,j){var l=0;if(e.nodeType==3){var k=e.data.toUpperCase().indexOf(j);if(k>=0){var h=document.createElement("span");h.className="highlight";var f=e.splitText(k);var c=f.splitText(j.length);var d=f.cloneNode(true);h.appendChild(d);f.parentNode.replaceChild(h,f);l=1}}else{if(e.nodeType==1&&e.childNodes&&!/(script|style)/i.test(e.tagName)){for(var g=0;g<e.childNodes.length;++g){g+=a(e.childNodes[g],j)}}}return l}return this.each(function(){a(this,b.toUpperCase())})};jQuery.fn.removeHighlight=function(){return this.find("span.highlight").each(function(){this.parentNode.firstChild.nodeName;with(this.parentNode){replaceChild(this.firstChild,this);normalize()}}).end()};

$.extend({
  getUrlVars: function(){
    var vars = [], hash;
    var cuts = (window.location.href.indexOf('#') > 0) ? window.location.href.indexOf('#') : undefined;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1, cuts).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
      hash = hashes[i].split('=');
      vars.push(hash[0]);
      vars[hash[0]] = hash[1];
    }
    return vars;
  },
  getUrlVar: function(name){
    return decodeURI($.getUrlVars()[name]);
  }
});
