<?php
	require_once("functions.php");
	
	$categories = get_config("categories");
	$sections_folder = repoicons_folder()."/Applications/Cydia.app/Sections/";
	$files = is_dir($sections_folder) ? scandir($sections_folder) : array();
	if (isset($categories["paquet"]) && isset($categories["paquet"]["id"])) {
		$package = get_package_for_identifier($categories["paquet"]["id"]);
	}
	
	if (isset($_POST['Create_categories'])) {
		cat_create_deb_folder();
		$categories = array("paquet" => array("id" => "com.sourcemanager.repoicons.".uniqid(), "nom" => localize("Icônes de la source"), "version" => "0.1"), "prefix" => "SourceManager ");
		set_config($categories, "categories");
		
		header("Refresh: 0");
		die();
	}
	
	if (isset($_POST['Create_package']) && isset($_POST['nom']) && isset($_POST['version']) && isset($_POST['id'])) {
		// Il faut tout vérifier avant de faire ça
		$categories['paquet'] = array("id" => $_POST['id'], "nom" => $_POST['nom'], "version" => $_POST['version']);
		set_config($categories, "categories");
		cat_build_package_repoicons_with_info($categories['paquet']);
		
		header("Refresh: 0");
		die();
	}
	
	function augmenter_version_refaire_paquet($sauver = true) {
		global $categories;
		$version = $categories['paquet']['version'];
		$dash_exp = '/\-([^\-]+)$|$/i';
		preg_match($dash_exp, $version, $matches);
		$d = intval(isset($matches[1])?$matches[1]:'0');
		$categories['paquet']['version'] = preg_replace($dash_exp, '-'.($d+1), $version, 1);
		
		if ($sauver)
			set_config($categories, "categories");
		cat_build_package_repoicons_with_info($categories['paquet']);
	}
	
	if (isset($_POST['Appliquer_prefix']) && isset($_POST['prefix'])) {
		$oldprefix = preg_replace('/\s+/', '_', $categories['prefix']);
		$newprefix = preg_replace('/\s+/', '_', sanitize_file_name($_POST['prefix']));
		
		foreach ($files as $file) {
			if ($file == "." || $file == "..") continue;
			if (strpos($file, $oldprefix) !== false || strlen($oldprefix) == 0)
				rename($sections_folder.$file, $sections_folder.str_replace($oldprefix, $newprefix, $file));
			else
				rename($sections_folder.$file, $sections_folder.$newprefix.$file);
		}
		
		$categories['prefix'] = str_replace('_', ' ', $newprefix);
		augmenter_version_refaire_paquet(false);
		set_config($categories, "categories");
		
		header("Refresh: 0");
		die();
	}
	
	if (isset($_POST['Ajouter_categorie']) && isset($_FILES['fichier'])) {
		$interdit = array("Addons.png", "Administration.png", "Archiving.png", "Blanks.png", "Books.png", "Carrier_Bundles.png", "Communication.png", "Data_Storage.png", "Development.png", "Dictionaries.png", "Education.png", "Entertainment.png", "Fonts.png", "Games.png", "Health_and_Fitness.png", "Imaging.png", "Java.png", "Keyboards.png", "Localization.png", "Messaging.png", "Multimedia.png", "Navigation.png", "Networking.png", "Packaging.png", "Productivity.png", "Repositories.png", "Ringtones.png", "Scripting.png", "Security.png", "Site-Specific_Apps.png", "Social.png", "Soundboards.png", "System.png", "Terminal_Support.png", "Text_Editors.png", "Themes.png", "Toys.png", "Tweaks.png", "Utilities.png", "Wallpaper.png", "WebClips.png", "Widgets.png", "X_Window.png");
		$fichier = $_FILES['fichier'];
		if ($fichier['type'] == "image/png" && preg_match("/\.png$/i", $fichier['name'])) {
			$path = $sections_folder . str_replace(' ', '_', sanitize_file_name($categories['prefix'] . $fichier['name']));
			if (!in_array(basename($path), $interdit))
				move_uploaded_file($fichier['tmp_name'], $path);
			
			augmenter_version_refaire_paquet();
			header("Refresh: 0");
			die();
		}
	}
	
	if (isset($_POST['Supprimer_categorie'])) {
		$index = intval($_POST['Supprimer_categorie']);
		if ($index > 1 && $index < count($files))
			unlink($sections_folder . $files[$index]);
		
		augmenter_version_refaire_paquet();
		header("Refresh: 0");
		die();
	}
	
	if (isset($_POST['Renommer_categorie'])) {
		// Le str_replace corrige des problèmes sur 000webhost
		$json = json_decode(str_replace("\\\"", "\"", $_POST['Renommer_categorie']), false);
		$index = $json->index;
		$name = $json->nom;
		if ($index > 1 && $index < count($files) && strlen($name) > 0) {
			$name = str_replace(' ', '_', $categories['prefix'] . $name) . ".png";
			rename($sections_folder . $files[$index], $sections_folder . $name);
		}
		
		augmenter_version_refaire_paquet();
		header("Refresh: 0");
		die();
	}
	
	if (isset($_POST['Supprimer_toutes_categories'])) {
		// Le dossier de travail
		$iconsfolder = repoicons_folder();
		if (is_dir($iconsfolder))
			rrmdir($iconsfolder);
		
		// Les overrides
		$overrides = get_config("overrides");
		if (isset($overrides[$categories["paquet"]["id"]]))
			unset($overrides[$categories["paquet"]["id"]]);
		set_config($overrides, "overrides");
		
		// Le .deb
		$debpath = debs_folder()."/repoicons.deb";
		if (file_exists($debpath))
			unlink($debpath);
		
		// La config
		delete_config("categories");
		
		update_packages_cache();
		header("Refresh: 0");
		die();
	}
?>
<?php html_head(); ?>
	<form enctype="multipart/form-data" method="post">
<?php if (count($categories) == 0): ?>
		<input name="Create_categories" type="submit" value="<?php echo localizehtml('Créer le paquet de catégories'); ?>" />
<?php else: ?>
		<fieldset>
			<legend><?php echo localizehtml("Paquet d'icônes"); ?></legend>
			<label><?php echo localizehtml("Nom"); ?> : <input name="nom" type="text" value="<?php echo htmlspecialchars($categories['paquet']['nom']); ?>" /></label><br />
			<label><?php echo localizehtml("Version"); ?> : <input name="version" pattern="[\d\.\-b]+" type="text" value="<?php echo htmlspecialchars($categories['paquet']['version']); ?>" /></label><br />
			<label><?php echo localizehtml("Identifiant"); ?> : <input name="id" pattern="[\w\.]+" size="50" type="text" value="<?php echo htmlspecialchars($categories['paquet']['id']); ?>" /> <?php echo localizehtml("(doit être unique pour chaque source)"); ?></label><br />
			<p><input name="Create_package" type="submit" value="<?php echo localizehtml('Régénérer le paquet d\'icônes'); ?>" /></p>
			<span>Version dans Cydia : <?php echo htmlspecialchars($package['Version']); ?></span><br />
			<span><?php echo localizehtml("(Cette version augmente automatiquement à chaque changement ci-dessous)"); ?></span>
		</fieldset>
		<fieldset>
			<legend><?php echo localizehtml("Catégories"); ?></legend>
			<label><?php echo localizehtml("Préfixe");?> : <input name="prefix" required="required" type="text" value="<?php echo htmlspecialchars($categories['prefix']); ?>" /></label>
			<input name="Appliquer_prefix" type="submit" value="<?php echo localizehtml('Appliquer'); ?>" />
			<label><?php echo localizehtml("Il est recommandé de finir ce champ par un espace."); ?></label>
			<table class="paquets">
				<colgroup>
					<col style="width: 25%; " />
					<col style="width: 25%; " />
					<col style="width: 25%; " />
					<col style="width: 25%; " />
				</colgroup>
				<thead>
					<tr>
<?php
	$tableths = array("Aperçu", "Nom de la catégorie", "Nom de fichier", "Actions");
	foreach ($tableths as $th) {
		echo "\t\t\t\t\t\t<th>".localizehtml($th)."</th>\n";
	}
?>
					</tr>
				</thead>
				<tbody>
<?php
	foreach ($files as $i => $file) {
		if ($file == "." || $file == "..") continue;
		echo '<tr>';
		echo '<td><img alt="'.htmlspecialchars($file).'" height="60" src="data:image/png;base64,' . base64_encode(file_get_contents($sections_folder.$file)) . '" width="60" /></td>';
		echo '<td>' . htmlspecialchars(str_replace('_', ' ', preg_replace('/\.png$/', '', $file))) . '</td>';
		echo '<td>' . htmlspecialchars($file) . '</td>';
		$catname = addslashes(preg_replace('/\.png$/','',str_replace(array(str_replace(' ','_',$categories['prefix']),'_'),array('',' '),$file)));
		echo '<td><button name="Supprimer_categorie" onclick="return confirm(\'Voulez-vous vraiment supprimer cette catégorie?\')" type="submit" value="'.$i.'">'.localizehtml("Supprimer").'</button>'.
		'<button name="Renommer_categorie" onclick="var nouvnom=prompt(\''.localizehtml("Nouveau nom").' :\',\''.$catname.'\');if(!nouvnom||nouvnom.length==0){return false};this.value=JSON.stringify({index:'.$i.',nom:nouvnom})" type="submit">'.localize("Renommer").'</button></td>';
		echo '</tr>';
	}
?>

				</tbody>
			</table>
			<fieldset>
				<legend><?php echo localizehtml("Ajouter une catégorie"); ?></legend>
				<label><?php echo localizehtml("Sélectionnez une icône"); ?> : <input accept="image/png" name="fichier" type="file" /> <?php echo localizehtml("(taille recommandée : 60px par 60px)"); ?></label><br />
				<input name="Ajouter_categorie" type="submit" value="<?php echo localizehtml('Ajouter la catégorie'); ?>" />
			</fieldset>
		</fieldset>
		<fieldset>
			<legend><?php echo localizehtml("Supprimer"); ?></legend>
			<input name="Supprimer_toutes_categories" onclick="return confirm('<?php echo localizehtml('Êtes-vous certain de vouloir supprimer le paquet de catégories de votre source?'); ?>')" type="submit" value="<?php echo localizehtml('Supprimer le paquet de catégories'); ?>" />
		</fieldset>
<?php endif; ?>
	</form>
<?php html_foot(); ?>