
var scrollAjax = true;

function haePikahaunTulos(keyword, list_id) {
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			var obj = JSON.parse(xhttp.responseText);
			var parentElem = document.getElementById("hlista");
			//parentElem.innerHTML = xhttp.responseText;
			console.log(xhttp.responseText);
			parentElem.innerHTML = '';
			var size = obj.record.length;
			for (var i = 0; i < size; i++) {
				var item = document.createElement("div");
				item.setAttribute("id", obj.record[i].id);
				var txt = obj.record[i].hakusana;
				var word = obj.record[i].hakusana;
				if (obj.record[i].homonyyminro) {
					txt = txt + "<sup>" + obj.record[i].homonyyminro + "</sup>";
					word = word + ":" + obj.record[i].homonyyminro;
				}
				if (obj.record[i].sanaluokka) {
					txt = txt + " <span>" + obj.record[i].sanaluokka + "</span>";
				}
				if (obj.record[i].tyyppi && obj.record[i].tyyppi == "V") {
					txt = txt + " <span>→</span>";
				}
				// linkki artikkeliin
				var link = document.createElement("a");
				link.setAttribute("href", "?p=qs-article&ks_id=" + obj.record[i].ks_id + "&word=" + word + "&list_id=" + obj.record[i].id + "&keyword=" + encodeURIComponent(keyword));
				link.innerHTML = txt;
				item.appendChild(link);
				parentElem.appendChild(item);
			}
			var target = document.getElementById(list_id);
			target.parentNode.scrollTop = target.offsetTop - target.parentNode.offsetTop;			
		}
  }
  console.log("list_id: " + list_id)
  xhttp.open("GET", "ajax.php?m=qs-results&prefix=" + encodeURIComponent(keyword) + 
			"&list_id=" + encodeURIComponent(list_id), true);
  xhttp.send();   
}


function listup(keyword) {
	if (scrollAjax) {
		var firstId = document.getElementById("hlista").firstElementChild.getAttribute("id");
		if (firstId <= 1) {
			return;
		}
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			scrollAjax = false;
			if (this.readyState == 4 && this.status == 200) {
				var obj = JSON.parse(xhttp.responseText);
				var parentElem = document.getElementById("hlista");
				while (parentElem.firstChild) {
					parentElem.removeChild(parentElem.firstChild);
				}	
				var size = obj.record.length;
				for (var i = size -1; i >= 0; i--) {
					var item = document.createElement("div");
					item.setAttribute("id", obj.record[i].id);
					var txt = obj.record[i].hakusana;
					var word = obj.record[i].hakusana;
					if (obj.record[i].homonyyminro) {
						txt = txt + "<sup>" + obj.record[i].homonyyminro + "</sup>";
						word = word + ":" + obj.record[i].homonyyminro;
					}
					if (obj.record[i].sanaluokka) {
						txt = txt + " <span>" + obj.record[i].sanaluokka + "</span>";
					}
					if (obj.record[i].tyyppi && obj.record[i].tyyppi == "V") {
						txt = txt + " <span>→</span>";
					}

					// linkki artikkeliin
					var link = document.createElement("a");
					link.setAttribute("href", "?p=qs-article&ks_id=" + obj.record[i].ks_id + "&word=" + word + "&list_id=" + obj.record[i].id + "&keyword=" + encodeURIComponent(keyword));
					link.innerHTML = txt;
					item.appendChild(link);
					//item.innerHTML = txt;
					parentElem.insertBefore(item, parentElem.firstChild);
				}
			}
			var target = document.getElementById(firstId);
			target.parentNode.scrollTop = target.offsetTop - target.parentNode.offsetTop;
			scrollAjax = true;
		}
		//xhttp.open("GET", "ajaxhaku.php?id=" + encodeURIComponent(firstId), true);
		//var min = Number(firstId)-100;
		//var max = firstId;
		xhttp.open("GET", "ajax.php?m=qs-results&prefix=" + encodeURIComponent(keyword) + 
					"&list_id=" + encodeURIComponent(firstId), true);
		xhttp.send();
	}
}

function listdown(keyword) {
	if (scrollAjax) {
		var thiselement = document.getElementById("hlista").lastElementChild;
		var lastId = thiselement.getAttribute("id");
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			scrollAjax = false;
			if (this.readyState == 4 && this.status == 200) {
				var obj = JSON.parse(xhttp.responseText);
				var parentElem = document.getElementById("hlista");
				while (parentElem.firstChild) {
					parentElem.removeChild(parentElem.firstChild);
				}				
				var size = obj.record.length;
				for (var i = 0; i < size; i++) {
					var item = document.createElement("div");
					item.setAttribute("id", obj.record[i].id);
					var txt = obj.record[i].hakusana;
					var word = obj.record[i].hakusana;
					if (obj.record[i].homonyyminro) {
						txt = txt + "<sup>" + obj.record[i].homonyyminro + "</sup>";
						word = word + ":" + obj.record[i].homonyyminro;
					}
					if (obj.record[i].sanaluokka) {
						txt = txt + " <span>" + obj.record[i].sanaluokka + "</span>";
					}                           
					if (obj.record[i].tyyppi && obj.record[i].tyyppi == "V") {
						txt = txt + " <span>→</span>";
					}
					// linkki artikkeliin
					var link = document.createElement("a");
					link.setAttribute("href", "?p=qs-article&ks_id=" + obj.record[i].ks_id + "&word=" + word + "&list_id=" + obj.record[i].id + "&keyword=" + encodeURIComponent(keyword));
					link.innerHTML = txt;
					item.appendChild(link);
					parentElem.appendChild(item);					
				}
			}
			var target = document.getElementById(lastId - 17);
			target.parentNode.scrollTop = target.offsetTop - target.parentNode.offsetTop;
			scrollAjax = true;
		}
		//xhttp.open("GET", "ajaxhaku.php?id=" + encodeURIComponent(lastId), true);
		//var min = lastId;
		//var max = lastId + 100;
		xhttp.open("GET", "ajax.php?m=qs-results&prefix=" + encodeURIComponent(keyword) + 
					"&list_id=" + encodeURIComponent(lastId), true);
		xhttp.send();
	}
}

function skrollaa(elem, keyword) {
	if (elem.scrollTop >= (elem.scrollHeight - elem.clientHeight))  {
		listdown(keyword);
	}
	if (elem.scrollTop <= 0) {
		listup(keyword);
	}
	//document.getElementById("scrollinfo").innerHTML = elem.scrollTop + " of " + elem.scrollHeight;
}
