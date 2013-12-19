<?php
	require_once("functions.php");
	
	$packages = get_packages_array();
	$overrides = get_config("overrides");
	ksort($overrides);
	
	if (isset($_GET['edit'])) {
		$edit_id = $_GET['edit'];
		if (isset($packages[$edit_id])) {
			$edit_package = $packages[$edit_id];
			
			// Gestion des réponses
			if (isset($_POST['Appliquer_modifications'])) {
				$update_cache = false;
				
				if (isset($_FILES['fichier'])) {
					$fichier = $_FILES['fichier'];
					if (preg_match('/\.deb$/i', $fichier['name'])) {
						// On le déplace temporairement pour la vérif
						$tmppath = tmp_folder(). "/" . sanitize_file_name($fichier['name']);
						if (move_uploaded_file($fichier['tmp_name'], $tmppath)) {
							// Il faut s'assurer d'avoir le bon .deb :P
							$control = control_of_deb($tmppath);
							if ($control['Package'] == $edit_id) {
								unlink(userdata_folder()."/" . $edit_package['Filename']);
								rename($tmppath, debs_folder()."/" . sanitize_file_name($control['Package']."_".$control['Version']."_".$control['Architecture'].".deb"));
							} else
								unlink($tmppath);
						}
						
						$update_cache = true;
					}
				}
				
				// Substitutions
				if (!isset($overrides[$edit_id]))
					$overrides[$edit_id] = array();
				
				if (isset($_POST['descriptionhtml']) && strlen($_POST['descriptionhtml']) > 0)
					$overrides[$edit_id]['Description'] = strip_tags($_POST['descriptionhtml'], '<a><span><b><i><code><em><kbd><strong><samp><var><img><br><q><sub><sup>');
				else
					unset($overrides[$edit_id]['Description']);
				
				if (isset($_POST['categorie'])) {
					$overrides[$edit_id]['Section'] = $_POST['categorie'];
					$update_cache = true;
				} else
					unset($overrides[$edit_id]['Section']);
				
				if (count($overrides[$edit_id]) == 0)
					unset($overrides[$edit_id]);
				set_config($overrides, "overrides");
				
				if ($update_cache === true)
					update_packages_cache();
				
				header("Refresh: 0");
				die();
			}
			
			// Correction importante :)
			if (isset($overrides[$edit_id]['screenshots']))
				$overrides[$edit_id]['screenshots'] = array_filter(array_values($overrides[$edit_id]['screenshots']));
			
			if (isset($_POST['Ajouter_screenshot']) && isset($_FILES['screenshot'])) {
				$fichier = $_FILES['screenshot'];
				if (preg_match('/\.png$/i', $fichier['name'])) {
					$new_path = get_uploaded_file_path($edit_id.".".uniqid().".png");
					if (move_uploaded_file($fichier['tmp_name'], $new_path)) {
						if (!isset($overrides[$edit_id]))
							$overrides[$edit_id] = array();
						
						if (!isset($overrides[$edit_id]['screenshots']))
							$overrides[$edit_id]['screenshots'] = array(basename($new_path));
						else
							$overrides[$edit_id]['screenshots'][] = basename($new_path);
						
						set_config($overrides, "overrides");
					}
				}
				
				header("Refresh: 0");
				die();
			}
			if (isset($overrides[$edit_id]) && isset($overrides[$edit_id]['screenshots'])) {
				if (isset($_POST['Screenshot_move_up'])) {
					$index = intval($_POST['Screenshot_move_up']);
					if ($index > 0 && isset($overrides[$edit_id]['screenshots'][$index])) {
						$screen = $overrides[$edit_id]['screenshots'][$index];
						$overrides[$edit_id]['screenshots'][$index] = $overrides[$edit_id]['screenshots'][$index - 1];
						$overrides[$edit_id]['screenshots'][$index - 1] = $screen;
						
						set_config($overrides, "overrides");
					}
					
					header("Refresh: 0");
					die();
				}
				if (isset($_POST['Screenshot_move_down'])) {
					$index = intval($_POST['Screenshot_move_down']);
					if ($index < count($overrides[$edit_id]['screenshots']) - 1 && isset($overrides[$edit_id]['screenshots'][$index])) {
						$screen = $overrides[$edit_id]['screenshots'][$index];
						$overrides[$edit_id]['screenshots'][$index] = $overrides[$edit_id]['screenshots'][$index + 1];
						$overrides[$edit_id]['screenshots'][$index + 1] = $screen;
						
						set_config($overrides, "overrides");
					}
					
					header("Refresh: 0");
					die();
				}
				if (isset($_POST['Screenshot_delete'])) {
					$index = intval($_POST['Screenshot_delete']);
					if (isset($overrides[$edit_id]['screenshots'][$index])) {
						unlink(get_uploaded_file_path($overrides[$edit_id]['screenshots'][$index]));
						unset($overrides[$edit_id]['screenshots'][$index]);
						if (count($overrides[$edit_id]['screenshots']) == 0)
							unset($overrides[$edit_id]['screenshots']);
						else
							$overrides[$edit_id]['screenshots'] = array_values($overrides[$edit_id]['screenshots']);
						
						set_config($overrides, "overrides");
					}
					
					header("Refresh: 0");
					die();
				}
			}
			
			if (isset($_POST['Supprimer_paquet'])) {
				// Supprimer le .deb
				unlink(userdata_folder()."/" . $edit_package['Filename']);
				// Les screenshots
				if (isset($overrides[$edit_id])) {
					foreach ($overrides[$edit_id]['screenshots'] as $screen) {
						unlink(get_uploaded_file_path($screen));
					}
				}
				// Les overrides
				unset($overrides[$edit_id]);
				set_config($overrides, "overrides");
				
				update_packages_cache();
				header("Refresh: 0");
				die();
			}
		}
	}
	
	if (isset($_POST['Regenerate_packages'])) {
		update_packages_cache();
		header("Refresh: 0");
		die();
	}
	
	$cats = cat_get_all_categories();
?>
<?php html_head(isset($_edit_package) ? '<script src="resources/upload_js.php"></script>' : ''); ?>
<?php if(!isset($edit_package)): ?>
	<p><a href="ajouterpaquet.php"><?php echo localizehtml("Ajouter un paquet"); ?></a></p>
	<p><?php echo localizehtml("Visible dans Cydia"); ?> :</p>
	<table class="paquets">
		<colgroup>
			<col style="width: 2em; " />
			<col style="width: 50px; " />
			<col style="width: 20%; " />
			<col style="width: 10%; " />
			<col style="width: 20%; " />
			<col style="width: auto; " />
		</colgroup>
		<thead>
			<tr>
<?php
	$tableths = array("No.", "Icône", "Nom", "Version", "Catégorie", "Identifiant");
	foreach ($tableths as $th) {
		echo "\t\t\t\t<th>".localizehtml($th)."</th>\n";
	}
?>
			</tr>
		</thead>
		<tbody>
<?php
	$i = 0;
	foreach ($packages as $package) {
		$i++;
		echo '<tr>';
		
		echo '<td>'.$i.'</td>';
		$sectionpath = null;
		if (isset($package['Section']) && isset($cats[$package['Section']]))
			$sectionpath = $cats[$package['Section']];
		if (!isset($sectionpath) || !$sectionpath || !file_exists($sectionpath))
			$sectionpath = api_folder()."/inconnu.png";
		echo '<td><img alt="" class="petiteicone" src="data:image/png;base64,'.base64_encode(file_get_contents($sectionpath)).'" /></td>';
		echo '<td><a href="?edit='.urlencode($package['Package']).'">' . htmlspecialchars($package['Name']) . '</a></td>';
		echo '<td>' . htmlspecialchars($package['Version']) . '</td>';
		echo '<td>' . (isset($package['Section']) ? htmlspecialchars($package['Section']) : "") . '</td>';
		echo '<td>' . htmlspecialchars($package['Package']) . '</td>';
		
		echo '</tr>';
	}
?>

		</tbody>
	</table>
	<form method="post">
		<fieldset>
			<legend><?php echo localizehtml("Régénération manuelle"); ?></legend>
			<p><?php echo localizehtml("Utilisez cette fonction si vous avez manuellement ajouté des fichiers .deb dans userdata/debs/ ou encore si vous avez des erreurs du type « Hash Sum Mismatch »."); ?></p>
			<p><input name="Regenerate_packages" type="submit" value="<?php echo localizehtml('Régénérer le Packages.gz'); ?>" /></p>
		</fieldset>
	</form>
<?php else: ?>
	<?php echo '<h3 class="headbleu">'.localizehtml("Édition de").' <q>'.htmlspecialchars($edit_package['Name']).'</q></h3>'."\n"; ?>
	<p><a href="paquets.php"><?php echo localizehtml("Retour aux paquets"); ?></a></p>
	<form action="paquets.php?edit=<?php echo htmlspecialchars($edit_id); ?>" enctype="multipart/form-data" id="upload_form" method="post" onsubmit="return check_files_size();">
		<div style="float: right; "><input name="Supprimer_paquet" onclick="return confirm('<?php echo localizehtml('Voulez-vous vraiment supprimer ce paquet et tout ce qui y est lié?'); ?>')" type="submit" value="<?php echo localizehtml('Supprimer le paquet'); ?>" /></div>
		<p><input name="Appliquer_modifications" type="submit" value="<?php echo localizehtml('Appliquer les modifications'); ?>" /></p>
		<fieldset>
			<legend><?php echo localizehtml("Remplacer le .deb"); ?> (<?php echo localizehtml("mettre à jour"); ?> - max. <?php echo size_string(parse_size_shorthand(ini_get("upload_max_filesize"))); ?>)</legend>
			<input accept="application/x-deb" name="fichier" type="file" /><br />
			<progress id="upload_progress" max="100" style="display: none; width: 250px; " value="0"></progress>
			<label id="upload_progress_label"></label>
		</fieldset>
		<fieldset>
			<legend><?php echo localizehtml("Description HTML"); ?> - <a href="<?php echo $config['info']['url'] . 'depiction/?package=' . $edit_id; ?>" target="_blank"><?php echo localizehtml("aperçu"); ?></a></legend>
			<textarea class="descriptionhtml" name="descriptionhtml" placeholder="<?php echo isset($edit_package['Description']) ? htmlspecialchars($edit_package['Description']) : ""; ?>"><?php echo isset($overrides[$edit_id]) && isset($overrides[$edit_id]['Description']) ? htmlspecialchars($overrides[$edit_id]['Description']) : ""; ?></textarea>
		</fieldset>
		<fieldset>
			<legend><?php echo localizehtml("Catégorie"); ?></legend>
			<label><?php echo localizehtml("Catégorie qui s'affiche dans Cydia"); ?> : <select name="categorie">
<?php
	$categorie = isset($overrides[$edit_id]) && isset($overrides[$edit_id]['Section']) ? $overrides[$edit_id]['Section'] : "";
	$deb_path = userdata_folder()."/".$edit_package['Filename'];
	$deb_control = control_of_deb($deb_path);
	echo '<optgroup label="'.localizehtml("Par défaut (dans le .deb)").'"><option value="'.(isset($deb_control['Section'])?$deb_control['Section']:"").'">'.(isset($deb_control['Section'])?$deb_control['Section']:"").'</option></optgroup>';
	
	echo '<optgroup label="'.localizehtml("Personnalisées").'">';
	foreach ($cats as $name => $file) {
		echo '<option value="'.htmlspecialchars($name).'"'.($name==$categorie?' selected="selected"':'').'>'.htmlspecialchars($name).'</option>';
	}
	echo '</optgroup>';
	
	$cydia = array("Addons", "Administration", "Archiving", "Blanks", "Books", "Carrier Bundles", "Communication", "Data Storage", "Development", "Dictionaries", "Education", "Entertainment", "Fonts", "Games", "Health and Fitness", "Imaging", "Java", "Keyboards", "Localization", "Messaging", "Multimedia", "Navigation", "Networking", "Packaging", "Productivity", "Repositories", "Ringtones", "Scripting", "Security", "Site-Specific Apps", "Social", "Soundboards", "System", "Terminal Support", "Text Editors", "Themes", "Toys", "Tweaks", "Utilities", "Wallpaper", "WebClips", "Widgets", "X Window");
	echo '<optgroup label="Cydia">';
	foreach ($cydia as $name) {
		echo '<option value="'.htmlspecialchars($name).'"'.($name==$categorie?' selected="selected"':'').'>'.htmlspecialchars($name).'</option>';
	}
	echo '</optgroup>';
?>

			</select></label>
		</fieldset>
		<fieldset>
			<legend><?php echo localizehtml("Captures d'écran"); ?></legend>
			<table class="paquets">
				<thead>
					<tr>
<?php
	$tableths = array("No.", "Nom", "Actions", "Aperçu");
	foreach ($tableths as $th) {
		echo "\t\t\t\t\t\t<th>".localizehtml($th)."</th>\n";
	}
?>
					</tr>
				</thead>
				<tbody>
<?php
	$screenshots = isset($overrides[$edit_id]) && isset($overrides[$edit_id]['screenshots']) ? $overrides[$edit_id]['screenshots'] : array();
	foreach ($screenshots as $i => $screen) {
		echo '<tr>';
		echo '<td>'.($i+1).'</td>';
		echo '<td>'.htmlspecialchars($screen).'</td>';
		echo '<td><button class="squarebtn" name="Screenshot_move_up" type="submit" value="'.$i.'">▲</button><button class="squarebtn" name="Screenshot_move_down" type="submit" value="'.$i.'">▼</button><button class="squarebtn dblwidth" name="Screenshot_delete" onclick="return confirm(\''.localizehtml("Êtes-vous certain de vouloir supprimer cette capture d'écran?", 1).'\')" type="submit" value="'.$i.'">'.localizehtml("Supprimer").'</button></td>';
		echo '<td><img alt="'.htmlspecialchars($screen).'" class="apercu" onclick="window.open(this.src,\'_blank\')" src="'.htmlspecialchars(get_uploaded_file_url($screen)).'" style="cursor: pointer; " /></td>';
		echo '</tr>';
	}
?>

				</tbody>
			</table>
			<fieldset>
				<legend><?php echo localizehtml("Ajouter une capture d'écran"); ?></legend>
				<input accept="image/png" name="screenshot" type="file" /><br />
				<input name="Ajouter_screenshot" type="submit" value="<?php echo localizehtml('Ajouter'); ?>" />
			</fieldset>
		</fieldset>
		<p><input name="Appliquer_modifications" type="submit" value="<?php echo localizehtml('Appliquer les modifications'); ?>" /></p>
	</form>
<?php endif; ?>
<?php html_foot(); ?>