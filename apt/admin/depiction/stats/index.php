<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="UTF-8" />
		<title>Téléchargements</title>
		<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0" name="viewport" />
		<link href="http://philippe97.ca/css/iphonecss/iphone.css" rel="stylesheet" />
		<script src="http://philippe97.ca/css/iphonecss/iphone.js"></script>
	</head>
	<body class="pinstripe">
		<div class="contenu">
<?php
	require_once("../../api/functions.php");
	
	$packages = get_packages_array();
	if (isset($_GET['package']) && isset($packages[$_GET['package']])) {
		$pkgid = $_GET['package'];
		$package = $packages[$pkgid];
	} else {
		$pkgid = "inconnu";
		$package = array("Package" => "inconnu", "Description" => "<span style=\"color: crimson; \">Aucun identifiant de paquet valide fourni.</span>");
	}
	
	echo "<h2>" . $package['Name'] . "</h2>";
	
	$stats = get_stats_for_packageid($pkgid);
	
	echo "<fieldset>";
	foreach ($stats as $key => $value) {
		echo "<div>";
		echo "<span>" . $key . "</span>";
		echo "<span class=\"label\">" . count($value) . "</span>";
		echo "</div>";
	}
	echo "</fieldset>";
?>

		</div>
	</body>
</html>