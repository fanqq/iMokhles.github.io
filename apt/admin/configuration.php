<?php
	require_once("functions.php");
	
	if (isset($_POST['changercode']) && isset($_POST['anciencode']) && isset($_POST['nouveaucode1']) && isset($_POST['nouveaucode2'])) {
		if ($config['user'] == md5($_POST['anciencode'])) {
			if ($_POST['nouveaucode1'] == $_POST['nouveaucode2']) {
				if (strlen($_POST['nouveaucode1']) > 0) {
					// On change le code !
					$config['user'] = md5($_POST['nouveaucode1']);
					set_config($config);
					$_SESSION['user'] = $config['user'];
					$succes = localize("Le code a bien été modifié.");
				} else
					$error = localize("Le code n'est pas assez long.");
			} else
				$error = localize("Les deux codes d'accès doivent correspondre.");
		} else
			$error = localize("L'ancien code d'accès ne correspond pas à l'actuel.");
	}
	if (isset($_POST['changerinfo']) && isset($_POST['nomsource']) && isset($_POST['nomcourtsource']) && isset($_POST['descsource'])) {
		$config['info']['nom'] = $_POST['nomsource'];
		$config['info']['nom_court'] = $_POST['nomcourtsource'];
		$config['info']['description'] = $_POST['descsource'];
		
		set_config($config);
		header("Refresh: 0");
		die();
	}
	if (isset($_POST['Modifier_icone']) && isset($_FILES['fichier_icone'])) {
		if (isset($config['info']['icone']))
			unlink(get_uploaded_file_path($config['info']['icone']));
		$fichier = $_FILES['fichier_icone'];
		if (preg_match('/\.png$/i', $fichier['name'])) {
			$new_path = get_uploaded_file_path("repoicon.png");
			if (move_uploaded_file($fichier['tmp_name'], $new_path)) {
				$config['info']['icone'] = basename($new_path);
			}
		}
		
		set_config($config);
		header("Refresh: 0");
		die();
	}
	if (isset($_POST['Supprimer_icone'])) {
		if (isset($config['info']['icone']))
			unlink(get_uploaded_file_path($config['info']['icone']));
		unset($config['info']['icone']);
		
		set_config($config);
		header("Refresh: 0");
		die();
	}
	if (isset($_POST['Reset_stats'])) {
		rrmdir(stats_folder());
		mkdir(stats_folder(), 0775);
		header("Refresh: 0");
		die();
	}
	if (isset($_POST['Reset_settings'])) {
		rrmdir(config_folder());
		mkdir(config_folder(), 0775);
		header("Refresh: 0");
		die();
	}
	if (isset($_POST['Reset_debs'])) {
		rrmdir(debs_folder());
		mkdir(debs_folder(), 0775);
		header("Refresh: 0");
		die();
	}
	if (isset($_POST['Reset_all'])) {
		rrmdir(dirname(admin_folder()));
		header("Refresh: 0");
		die();
	}
?>
<?php html_head(); ?>
<?php if (isset($error)): ?>
	<p class="erreurlaite"><?php echo htmlspecialchars($error); ?></p>
<?php elseif (isset($succes)): ?>
<p class="succeslaite"><?php echo htmlspecialchars($succes); ?></p>
<?php endif; ?>
	<form method="post">
		<fieldset>
			<legend><?php echo localizehtml("Informations de la source"); ?></legend>
			<table><tbody>
				<tr>
					<td><label for="nomsource"><?php echo localizehtml("Nom de la source"); ?> :</label></td>
					<td><input id="nomsource" name="nomsource" type="text" value="<?php echo $config['info']['nom']; ?>" /></td>
				</tr>
				<tr>
					<td><label for="nomcourtsource"><?php echo localizehtml("Nom court de la source"); ?> :</label></td>
					<td><input id="nomcourtsource" name="nomcourtsource" type="text" value="<?php echo $config['info']['nom_court']; ?>" /></td>
				</tr>
				<tr>
					<td><label for="descsource"><?php echo localizehtml("Description de la source"); ?> :</label></td>
					<td><input id="descsource" name="descsource" type="text" value="<?php echo $config['info']['description']; ?>" /></td>
				</tr>
				<tr>
					<td colspan="2"><input name="changerinfo" type="submit" value="<?php echo localizehtml('Valider'); ?>" /></td>
				</tr>
			</tbody></table>
		</fieldset>
	</form>
	<form enctype="multipart/form-data" method="post">
		<fieldset>
			<legend><?php echo localizehtml("Icône de la source"); ?></legend>
			<label><?php echo localizehtml("Téléverser un fichier"); ?> : <input accept="image/png" name="fichier_icone" type="file" /> <?php echo localizehtml("(taille recommandée : 60px par 60px)"); ?>.</label><br />
			<input name="Modifier_icone" type="submit" value="<?php echo localizehtml('Modifier l\'icône'); ?>" />
<?php if(isset($config['info']['icone'])): ?>
			<input name="Supprimer_icone" onclick="return confirm('<?php echo localizehtml('Êtes-vous certain?'); ?>')" type="submit" value="<?php echo localizehtml('Supprimer l\'icône'); ?>" />
<?php endif; ?>
		</fieldset>
	</form>
	<form method="post">
		<fieldset>
			<legend><?php echo localizehtml("Code d'accès"); ?></legend>
			<table><tbody>
				<tr>
					<td><label for="anciencode"><?php echo localizehtml("Ancien code d'accès"); ?> :</label></td>
					<td><input autocomplete="off" id="anciencode" name="anciencode" required="required" type="password" /></td>
				</tr>
				<tr>
					<td><label for="nouveaucode1"><?php echo localizehtml("Nouveau code d'accès"); ?> :</label></td>
					<td><input autocomplete="off" id="nouveaucode1" name="nouveaucode1" required="required" type="password" /></td>
				</tr>
				<tr>
					<td><label for="nouveaucode2"><?php echo localizehtml("Nouveau code d'accès (encore)"); ?> :</label></td>
					<td><input autocomplete="off" id="nouveaucode2" name="nouveaucode2" required="required" type="password" /></td>
				</tr>
				<tr>
					<td colspan="2"><input name="changercode" type="submit" value="<?php echo localizehtml('Valider'); ?>" /></td>
				</tr>
			</tbody></table>
		</fieldset>
	</form>
	<form method="post">
		<fieldset>
			<legend><?php echo localizehtml("Je suis fou"); ?></legend>
			<input name="Reset_stats" onclick="return confirm('<?php echo localizehtml('Cette action est irréversible, voulez-vous vraiment continuer?'); ?>')" type="submit" value="<?php echo localizehtml('Réinitialiser les statistiques'); ?>" /><br />
			<input name="Reset_settings" onclick="return confirm('<?php echo localizehtml('Cette action est irréversible, voulez-vous vraiment continuer?'); ?>')" type="submit" value="<?php echo localizehtml('Réinitialiser tous les réglages (reconfigurer)'); ?>" /><br />
			<input name="Reset_debs" onclick="return confirm('<?php echo localizehtml('Cette action est irréversible, voulez-vous vraiment continuer?'); ?>')" type="submit" value="<?php echo localizehtml('Supprimer tous les debs'); ?>" /><br />
			<input name="Reset_all" onclick="return confirm('<?php echo localizehtml('Cette action est irréversible, voulez-vous vraiment continuer?'); ?>')" type="submit" value="<?php echo localizehtml('Supprimer toute la source'); ?>" />
		</fieldset>
	</form>
<?php html_foot(); ?>