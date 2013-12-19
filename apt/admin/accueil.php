<?php require_once("functions.php");
	
	$vinfo = file_get_contents("http://sourcemanager.philippe97.ca/current.php?sourceurl=".urlencode($_config['info']['url'])."&version=".urlencode(SOURCEMANAGER_VERSION));
	$vinfo = json_decode($vinfo, true);
	
	if (isset($_POST['Upgrade_source'])) {
		$upgrade_script = file_get_contents($vinfo['upgrade']);
		file_put_contents('../upgrade.php', $upgrade_script);
		header('Location: ../upgrade.php');
	}
	
?>
<?php html_head(); ?>
	<p><?php echo localizehtml("Bienvenue dans le panneau d'administration de SourceManager!"); ?><br /><?php echo localizehtml("N'hésitez pas à explorer les onglets :)"); ?></p>
	<p><?php echo localizehtml("Vous possédez la version")." ".SOURCEMANAGER_VERSION.", ".localizehtml("vérifiez les mises à jour depuis"); ?> <a href="http://sourcemanager.philippe97.ca/"><?php echo localizehtml("le site de SourceManager"); ?></a>.</p>
<?php
	if ($vinfo && $vinfo['version'] != SOURCEMANAGER_VERSION) {
		echo '<h2>'.localizehtml("Mise à jour disponible!").' ('.htmlspecialchars($vinfo['version']).')</h2>';
		echo '<form method="post"><input name="Upgrade_source" type="submit" value="'.localizehtml("Mettre à jour maintenant!").'" /></form>';
	}
?>
<?php html_foot(); ?>