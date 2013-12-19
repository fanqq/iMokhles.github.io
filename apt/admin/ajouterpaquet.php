<?php
	require_once("functions.php");
	
	$folder = debs_folder()."/";
	
	if (isset($_FILES['fichier'])) {
		$fichier = $_FILES['fichier'];
		if (preg_match('/\.deb$/i', $fichier['name'])) {
			$path = $folder . sanitize_file_name($fichier['name']);
			if (move_uploaded_file($fichier['tmp_name'], $path) && $config['debs']['rename'] === true) {
				// Renommer le deb :)
				$control = control_of_deb($path);
				if ($control != null && isset($control['Package']) && isset($control['Version']) && isset($control['Architecture'])) {
					$debpath = $folder . sanitize_file_name($control['Package']."_".$control['Version']."_".$control['Architecture'].".deb");
					rename($path, $debpath);
				}
			}
		}
		
		update_packages_cache();
		die();
	}
?>
<?php html_head('<script src="resources/upload_js.php"></script>'); ?>
	<form action="ajouterpaquet.php" data-post-redirect="paquets.php" enctype="multipart/form-data" id="upload_form" method="post" onsubmit="return check_files_size();">
		<fieldset>
			<legend><?php echo localizehtml("Fichier .deb"); ?> (max. <?php echo size_string(parse_size_shorthand(ini_get("upload_max_filesize"))); ?>)</legend>
			<input accept="application/x-deb" name="fichier" required="required" type="file" />
		</fieldset>
		<p><?php echo localizehtml("Vous pourrez modifier les informations du paquet après l'avoir ajouté."); ?></p>
		<div>
			<progress id="upload_progress" max="100" style="display: none; width: 250px; " value="0"></progress>
			<label id="upload_progress_label"></label>
		</div>
		<button type="submit"><?php echo localizehtml("Ajouter le paquet"); ?></button>
		<button onclick="window.location.assign('paquets.php');return false"><?php echo localizehtml("Retour"); ?></button>
	</form>
<?php html_foot(); ?>