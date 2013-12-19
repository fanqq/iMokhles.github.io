<?php
	require_once("functions.php");
	
	if (isset($_POST['motdepasse'])) {
		$mdp = md5($_POST['motdepasse']);
		if ($mdp == $config['user']) {
			$_SESSION['user'] = $mdp;
			header("Location: index.php");
			die();
		}
	}
?>
<?php html_head(); ?>
	<form method="post">
		<input autofocus="autofocus" name="motdepasse" placeholder="<?php echo localizehtml('Code d\'accès'); ?>" type="password" />
		<input type="submit" value="<?php echo localizehtml('Connexion'); ?>" />
	</form>
<?php html_foot(); ?>