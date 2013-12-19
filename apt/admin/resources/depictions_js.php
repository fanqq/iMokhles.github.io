<?php
	require_once("../api/functions.php");
	
	header("Content-Type: text/javascript; charset=UTF-8");
	
	$config = get_config();
	$depiction = get_config("depiction");
?>
// Variables fournies par PHP
var sourceurl = "<?php echo $config['info']['url']; ?>";
var currentjson = <?php echo json_encode($depiction); ?>;

function show_error() {
	document.getElementById("depictions").appendChild(document.createTextNode("<?php echo localizehtml('Votre navigateur ne supporte pas les technologies nécessaires pour la modification des depictions.'); ?>"));
}

function pageloaded() {
	if (typeof(JSON) === 'undefined' || typeof(FileReader) === 'undefined') {
		show_error();
		return;
	}
	
	window._depiction_div = document.getElementById("depictions");
	
	var saveform = _depiction_div.appendChild(document.createElement("form"));
	saveform.method = "post";
	var savebutton = saveform.appendChild(document.createElement("p")).appendChild(document.createElement("button"));
	savebutton.type = "submit";
	savebutton.appendChild(document.createTextNode("<?php echo localizehtml('Enregistrer les changements'); ?>"));
	saveform.onsubmit = function() {
		window.currentjson = parse_dom_to_array();
		savejsoninput.value = encodeURIComponent(JSON.stringify(window.currentjson));
	}
	var savejsoninput = saveform.appendChild(document.createElement("input"));
	savejsoninput.name = "json";
	savejsoninput.type = "hidden";
	
	window._apercu_div = _depiction_div.appendChild(document.createElement("div")).appendChild(document.createElement("div"));
	_apercu_div.className = "apercu_div";
	var apercuform = _apercu_div.parentNode.insertBefore(document.createElement("form"), _apercu_div);
	apercuform.action = sourceurl + "depiction/";
	apercuform.method = "post";
	apercuform.target = "apercu_iframe";
	apercuform.onsubmit = function() {
		apercuBtn.value = encodeURIComponent(JSON.stringify(parse_dom_to_array()));
		//console.log(apercuBtn.value);
	}
	apercuform.appendChild(document.createElement("h3").appendChild(document.createTextNode("<?php echo localizehtml('Aperçu'); ?> : ")).parentNode);
	var apercuBtn = apercuform.childNodes[0].appendChild(document.createElement("button"));
	apercuBtn.name = "customjson";
	apercuBtn.type = "submit";
	apercuBtn.appendChild(document.createTextNode("<?php echo localizehtml('Régénérer'); ?>"));
	
	// On rajoute l'iframe
	var apercuiframe = document.createElement("iframe");
	apercuiframe.setAttribute("name", "apercu_iframe");
	_apercu_div.appendChild(apercuiframe);
	
	// La structure de droite
	window._semantic_div = _depiction_div.appendChild(document.createElement("div")).appendChild(document.createElement("div"));
	_semantic_div.className = "semantic_div";
	_semantic_div.parentNode.insertBefore(document.createElement("h3").appendChild(document.createTextNode("<?php echo localizehtml('Sémantique'); ?> : ")).parentNode, _semantic_div);
	render_currentjson();
	
	//Aperçu initial
	console.log(parse_dom_to_array());
	apercuBtn.click();
}
if (window.addEventListener) {
	window.addEventListener("DOMContentLoaded", pageloaded, false);
} else if (window.attachEvent) {
	window.attachEvent("onload", show_error);
}

function generate_move_buttons() {
	var btns = {
		"▲": function() {
			var cell = this.parentNode.parentNode;
			if (cell.previousSibling)
				cell.parentNode.insertBefore(cell, cell.previousSibling);
		},
		"▼": function() {
			var cell = this.parentNode.parentNode;
			if (cell.nextSibling && cell.nextSibling.getAttribute("data-type")) {
				if (cell.nextSibling.nextSibling)
					cell.parentNode.insertBefore(cell, cell.nextSibling.nextSibling);
				else
					cell.parentNode.appendChild(cell);
			}
		},
		"X": function() {// ✘
			var cell = this.parentNode.parentNode;
			cell.parentNode.removeChild(cell);
		}
	}
	var editspan = document.createElement("span");
	editspan.setAttribute("class", "move_btns");
	for (var name in btns) {
		var btn = editspan.appendChild(document.createElement("button"));
		btn.onclick = btns[name];
		btn.appendChild(document.createTextNode(name));
	}
	return editspan;
}

function render_currentjson() {
	// Vider le div (au cas où)
	while (_semantic_div.childNodes.length)
		_semantic_div.removeChild(_depiction_div.childNodes[0]);
	
	function rajouter_rajouteur(element, boutons) {
		var ajouterdiv = element.appendChild(document.createElement("div"));
		ajouterdiv.setAttribute("class", "ajouter_div");
		var ajouterspan = ajouterdiv.appendChild(document.createElement("span"));
		ajouterspan.appendChild(document.createTextNode("<?php echo localizehtml('Ajouter'); ?> : "));
		for (var name in boutons) {
			var btn = ajouterdiv.appendChild(document.createElement("button"));
			btn.appendChild(document.createTextNode(name));
			btn.onclick = boutons[name];
		}
	}
	
	function rendre_super_cellule(element, content) {
		// Sélection
		var selectionLabel = element.appendChild(document.createElement("label"));
		selectionLabel.appendChild(document.createTextNode("<?php echo localizehtml('Sélection'); ?> : "));
		var selectionSelect = selectionLabel.appendChild(document.createElement("select"));
		selectionSelect.name = "selection";
		var selectionValues = {
			"<?php echo localizehtml('Bleue'); ?>": "default",
			"<?php echo localizehtml('Grise'); ?>": "gray",
			"<?php echo localizehtml('Verte'); ?>": "green",
			"<?php echo localizehtml('Rouge'); ?>": "red"
		}
		for (var name in selectionValues) {
			var opt = selectionSelect.appendChild(document.createElement("option"));
			opt.value = selectionValues[name];
			opt.appendChild(document.createTextNode(name));
			if (content && content.selection == selectionValues[name])
				opt.selected = true;
		}
		
		// Titre
		var titreLabel = element.appendChild(document.createElement("label"));
		titreLabel.appendChild(document.createTextNode("<?php echo localizehtml('Titre'); ?> : "));
		var titreInput = titreLabel.appendChild(document.createElement("input"));
		titreInput.name = "titre";
		titreInput.placeholder = "ex: Twitter";
		titreInput.type = "text";
		
		// Valeur
		var valeurLabel = element.appendChild(document.createElement("label"));
		valeurLabel.appendChild(document.createTextNode("<?php echo localizehtml('Valeur'); ?> : "));
		var valeurInput = valeurLabel.appendChild(document.createElement("input"));
		valeurInput.name = "valeur";
		valeurInput.placeholder = "ex: @Philippe972";
		valeurInput.type = "text";
		
		// Titre
		var urlLabel = element.appendChild(document.createElement("label"));
		urlLabel.appendChild(document.createTextNode("URL : "));
		var urlInput = urlLabel.appendChild(document.createElement("input"));
		urlInput.name = "url";
		urlInput.placeholder = "ex: http://twitter.com/";
		urlInput.type = "url";
		
		var iconLabel = element.appendChild(document.createElement("label"));
		iconLabel.appendChild(document.createTextNode("<?php echo localizehtml('Icône'); ?> : "));
		var iconFile = iconLabel.appendChild(document.createElement("input"));
		iconFile.type = "file";
		var iconImg = iconLabel.appendChild(document.createElement("img"));
		iconImg.className = "icone";
		
		var fr = new FileReader();
		fr.onload = function(e) {
			iconImg.src = e.target.result;
		}
		iconFile.onchange = function() {
			if (this.files.length == 0)
				return;
			fr.readAsDataURL(this.files[0]);
		}
		
		if (content) {
			titreInput.value = content.titre;
			valeurInput.value = content.valeur;
			urlInput.value = content.url;
			iconImg.src = content.icone;
		}
	}
	
	function rajouter_rajouteur_section(element) {
		function get_section_div(type) {
			var newdiv = document.createElement("div");
			newdiv.setAttribute("data-type", type);
			newdiv.appendChild(generate_move_buttons());
			return newdiv;
		}
		var boutons = {
			"desc": function() {
				var before = this.parentNode;
				var section = before.parentNode;
				var newdiv = section.insertBefore(get_section_div("description"), before);
				newdiv.appendChild(document.createTextNode("<?php echo localizehtml('Description du paquet'); ?>"));
			},
			"screens": function() {
				var before = this.parentNode;
				var section = before.parentNode;
				var newdiv = section.insertBefore(get_section_div("screenshots"), before);
				newdiv.appendChild(document.createTextNode("<?php echo localizehtml('Screenshots du paquet'); ?>"));
			},
			"dls": function() {
				var before = this.parentNode;
				var section = before.parentNode;
				var newdiv = section.insertBefore(get_section_div("downloads"), before);
				newdiv.appendChild(document.createTextNode("<?php echo localizehtml('Downloads du paquet'); ?>"));
			},
			"cell": function() {
				var before = this.parentNode;
				var section = before.parentNode;
				var newdiv = section.insertBefore(get_section_div("cell"), before);
				rendre_super_cellule(newdiv);
			}
		}
		rajouter_rajouteur(element, boutons);
	}
	
	// Ajouter les éléments
	for (var i = 0; i < currentjson.length; i++) {
		var type = currentjson[i].type;
		var content = currentjson[i].content;
		var newdiv = _semantic_div.appendChild(document.createElement("div"));
		newdiv.setAttribute("data-type", type);
		newdiv.appendChild(generate_move_buttons());
		if (type == "header" || type == "footer") {
			newdiv.appendChild(document.createElement("h4")).appendChild(document.createTextNode("Type : "+type));
			// Contenu :
			var input = newdiv.appendChild(document.createElement("input"));
			input.type = "text";
			input.value = content;
		} else if (type == "section") {
			for (var j = 0; j < content.length; j++) {
				var cell = content[j];
				var cellType = cell.type;
				var newCellDiv = newdiv.appendChild(document.createElement("div"));
				newCellDiv.setAttribute("data-type", cellType);
				newCellDiv.appendChild(generate_move_buttons());
				if (cellType == "description")
					newCellDiv.appendChild(document.createTextNode("<?php echo localizehtml('Description du paquet'); ?>"));
				else if (cellType == "screenshots")
					newCellDiv.appendChild(document.createTextNode("<?php echo localizehtml('Screenshots du paquet'); ?>"));
				else if (cellType == "downloads")
					newCellDiv.appendChild(document.createTextNode("<?php echo localizehtml('Downloads du paquet'); ?>"));
				else {
					// Super cellule :)
					rendre_super_cellule(newCellDiv, content[j].content);
				}
			}
			
			// Rajouter le rajouteur
			rajouter_rajouteur_section(newdiv);
		}
	}
	
	// Ajouter le rajouteur
	function get_section_div(type) {
		var newdiv = document.createElement("div");
		newdiv.setAttribute("data-type", type);
		newdiv.appendChild(generate_move_buttons());
		return newdiv;
	}
	var ajouterliens = {
		"header": function() {
			var newdiv = _semantic_div.insertBefore(get_section_div("header"), this.parentNode);
			newdiv.appendChild(document.createElement("h4")).appendChild(document.createTextNode("Type : header"));
			newdiv.appendChild(document.createElement("input")).type = "text";
		},
		"section": function() {
			var newdiv = _semantic_div.insertBefore(get_section_div("section"), this.parentNode);
			// Rajouter le rajouteur
			rajouter_rajouteur_section(newdiv);
		},
		"footer": function() {
			var newdiv = _semantic_div.insertBefore(get_section_div("footer"), this.parentNode);
			newdiv.appendChild(document.createElement("h4")).appendChild(document.createTextNode("Type : footer"));
			newdiv.appendChild(document.createElement("input")).type = "text";
		}
	}
	rajouter_rajouteur(_semantic_div, ajouterliens);
}

function parse_dom_to_array() {
	var final_array = [];
	var root_elems = document.querySelectorAll('.semantic_div > [data-type]');
	for (var i = 0; i < root_elems.length; i++) {
		var root_elem = root_elems[i];
		var type = root_elem.getAttribute("data-type");
		if (type == "header" || type == "footer")
			final_array.push({type: type, content: root_elem.querySelector('input[type="text"]').value});
		else if (type == "section") {
			var content = [];
			var cells = root_elem.querySelectorAll('[data-type]');
			for (var j = 0; j < cells.length; j++) {
				var cell = cells[j];
				var type = cell.getAttribute("data-type");
				if (type == "description" || type == "screenshots" || type == "downloads")
					content.push({type: type});
				else {
					// Super cellule
					var content2 = {
						"selection": cell.querySelector('select[name="selection"]').value,
						"titre": cell.querySelector('input[name="titre"]').value,
						"valeur": cell.querySelector('input[name="valeur"]').value,
						"url": cell.querySelector('input[name="url"]').value,
						"icone": cell.querySelector('img.icone').src
					};
					content.push({type: "cell", content: content2});
				}
			}
			final_array.push({type: "section", content: content});
		}
	}
	return final_array;
}

function confirmerFermeture() {
	if (JSON.stringify(window.currentjson) != JSON.stringify(parse_dom_to_array()))
		return "<?php echo localizehtml('Vous avez effectué des modifications qui n\'ont pas été enregistrées.'); ?>";
	return undefined;
}
if (window.addEventListener) {
	window.addEventListener("beforeunload", confirmerFermeture, false);
}