<?php
	require_once("functions.php");
	
	clearstatcache();
	if (!is_writable(admin_folder()) || !is_writable(dirname(admin_folder())) || !is_writable(userdata_folder())) {
		die("Le dossier contenant cette source n'est pas &eacute;crivable, veuillez changer ses permissions pour 0777 de fa&ccedil;on r&eacute;currente.");
	}
	
	if (isset($_POST['motdepasse1']) && isset($_POST['motdepasse2']) && isset($_POST['nomsource']) && isset($_POST['description']) && isset($_POST['sourceurl'])) {
		if ($_POST['motdepasse1'] == $_POST['motdepasse2']) {
			$config = array("user" => md5($_POST['motdepasse1']),
							"info" => array(
											"nom" => $_POST['nomsource'],
											"nom_court" => $_POST['nomsource'],
											"description" => $_POST['description'],
											"url" => $_POST['sourceurl']
											),
							"debs" => array(
											"rename" => true
											)
							);
			set_config($config);
			
			// Créer les .htaccess
			$basepath = dirname(dirname($_SERVER['REQUEST_URI']));
			/*file_put_contents("../.htaccess", "RewriteEngine on\nRewriteBase ".$basepath."\nRewriteRule ^(Packages\.gz|Release|.+\.deb|CydiaIcon\.png|uploads\/[^\/]+)$ admin/api/rewrite.php?file=$1\nRewriteRule ^(debs|depiction|stats)\\/?(.*)$ admin/$1/$2\nIndexIgnore *\nphp_value auto_append_file none");
			file_put_contents("../admin/.htaccess", "ErrorDocument 404 ".$basepath."/admin/404.php");
			file_put_contents("../admin/api/.htaccess", "order allow,deny\ndeny from all\n<FilesMatch \"^rewrite.php$\">\nallow from all\n</FilesMatch>");
			file_put_contents("../userdata/.htaccess", "order allow,deny\ndeny from all");*/
			include api_folder()."/sourcesetup.php";
			
			header("Location: index.php");
			die();
		} else
			$error = "Les deux codes d'accès doivent correspondre.";
	}
?>
<?php html_head(); ?>
<?php if (isset($error)): ?>
		<p class="erreurlaite"><?php echo $error; ?></p>
<?php endif; ?>
		<form method="post">
			<fieldset>
				<legend><?php echo localizehtml("Administration"); ?></legend>
				<label for="mdp"><?php echo localizehtml("Code d'accès"); ?> :</label>
				<input id="mdp" name="motdepasse1" required="required" type="password" /><br />
				<label for="mdp"><?php echo localizehtml("Code d'accès (encore)"); ?> :</label>
				<input id="mdp" name="motdepasse2" required="required" type="password" />
			</fieldset>
			<fieldset>
				<legend><?php echo localizehtml("Informations"); ?></legend>
				<label for="nom"><?php echo localizehtml("Nom de la source"); ?> :</label>
				<input id="nom" name="nomsource" required="required" type="text" /><br />
				<label for="desc"><?php echo localizehtml("Description de la source"); ?> :</label>
				<input id="desc" name="description" required="required" type="text" /><br />
				<label for="url"><?php echo localizehtml("URL de la source (ne changer que si vous savez ce que vous faites)"); ?> :</label>
				<input id="url" name="sourceurl" required="required" type="url" />
				<script>/*<![CDATA[*/document.getElementById('url').value = window.location.href.replace(/[^\/]+\/[^\/]+$/, '');/*]]>*/</script>
			</fieldset>
			<p><input type="submit" value="<?php echo localizehtml('Commencer à utiliser la source!'); ?>" /></p>
		</form>
<?php html_foot(); ?>