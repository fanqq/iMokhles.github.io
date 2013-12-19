<?php
	header("Content-Type: text/javascript; charset=UTF-8");
	
	function parse_size_shorthand($value) {
		if (is_numeric($value)) {
			return $value;
		} else {
			$value_length = strlen( $value );
			$qty = substr( $value, 0, $value_length - 1 );
			$unit = strtolower( substr( $value, $value_length - 1 ) );
			switch ( $unit ) {
				case 'k':
					$qty *= 1024;
					break;
				case 'm':
					$qty *= 1048576;
					break;
				case 'g':
					$qty *= 1073741824;
					break;
			}
			return $qty;
		}
	}
?>
window.max_file_size = <?php echo parse_size_shorthand(ini_get("upload_max_filesize")); ?>;

function size_string(kilos) {
	var size = kilos;
	unit = "Ko";
	if (size > 1024) {
		size /= 1024;
		unit = "Mo";
	}
	if (size > 1024) {
		size /= 1024;
		unit = "Go";
	}
	return (Math.round(size*100)/100) + " " + unit;
}

function check_files_size() {
	var input = document.querySelector('#upload_form input[type="file"]');
	if (input.files && input.files.length > 0) {
		var totalsize = 0;
		for (var i = 0; i < input.files.length; i++)
			totalsize += input.files[i].size;
		if (totalsize > max_file_size)
			alert("La taille totale des fichiers à téléverser est trop grande pour la configuration du serveur.");
		else
			upload_files();
		return false;
	}
	return true;
}
function upload_files() {
	var form = document.getElementById('upload_form');
	// Tout désactiver
	var to_disable = form.elements;
	for (var i = 0; i < to_disable.length; i++)
		to_disable[i].disabled = true;
	
	// Pas de moyen plus efficace :)
	document.getElementById("upload_progress").style.removeProperty("display");
	
	var http = new XMLHttpRequest();
	http.upload.addEventListener("progress", update_progress, false);
	http.addEventListener("load", upload_loaded, false);
	http.open("POST", form.action, true);
	
	/*if (!navigator.userAgent.match(/MSIE/) && FormData.constructor.length == 1)
	 http.send(new FormData(document.getElementsByName("fichiers[]")[0].form));*/
	var fd = new FormData();
	var inputs = to_disable;
	for (var i = 0; i < inputs.length; i++) {
		var input = inputs[i];
		if (input.type == "file")
			for (var j = 0; j < input.files.length; j++)
				fd.append(input.name, input.files[j]);
		else
			fd.append(input.name, input.value);
	}
	http.send(fd);
}
function update_progress(e) {
	var progress = document.getElementById("upload_progress");
	var label = document.getElementById("upload_progress_label");
	if (e.lengthComputable == true && e.total > 0) {
		var percent = Math.round(e.loaded / e.total * 100);
		progress.value = percent;
		
		var now = (new Date()).getTime();
		if (window.lastprogress) {
			// On divise le nombre de Ko par le nombre de secondes pour avoir la vitesse en Ko/sec.
			var secondes = (now - window.lastprogress.time) / 1000;
			var loaded = (e.loaded - window.lastprogress.loaded) / 1024;
			label.innerHTML = percent + " % à " + size_string(Math.round(loaded / secondes)) + "/sec";
		} else {
			label.innerHTML = percent + " %";
			window.lastprogress = {loaded:e.loaded, time:now};
		}
	} else {
		progress.value = 0;
		label.innerHTML = "Chargement..." + e.loaded;
	}
}
function upload_loaded(e) {
	delete window.lastprogress;
	document.getElementById("upload_progress_label").innerHTML = e.target.status == 200 ? "Terminé!" : "Erreur ("+ e.target.status+")";
	if (e.target.status == 200) {
		var form = document.getElementById('upload_form');
		var redirect = form.getAttribute("data-post-redirect");
		window.location.replace(redirect ? redirect : form.action);
	}
}

function confirmerFermeture() {
	if (window.lastprogress)
		return "Un téléversement est toujours en cours...";
	return undefined;
}
if (window.addEventListener) {
	window.addEventListener("beforeunload", confirmerFermeture, false);
} else if (window.attachEvent) {
	window.attachEvent("onbeforeunload", confirmerFermeture);
}

/* Liste des .debs */

function selectionner_tout() {
	var inputs = document.getElementById("fichiers_table").getElementsByTagName("input");
	for (var i = 0; i < inputs.length; i++)
		if (inputs[i].type == "checkbox")
			inputs[i].checked = true;
}