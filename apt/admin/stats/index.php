<?php require_once("../api/functions.php"); ?>
<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="UTF-8" />
		<title>Statistiques</title>
		<meta content="15" http-equiv="Refresh" />
		<link href="http://philippe97.ca/css/iphonecss/iphone.css" rel="stylesheet" />
		<script src="http://philippe97.ca/css/iphonecss/iphone.js"></script>
	</head>
	<body class="pinstripe">
		<div class="navbar">
			<h1><?php echo localizehtml("Statistiques"); ?></h1>
		</div>
		<div class="contenu">
<?php
	echo "<fieldset><div><span>".localizehtml("Utilisateurs")."</span><span class=\"label\">".count(get_all_udid_list())."</span></div></fieldset>";
	
	// Applications
	$all_packages = get_packages_array();
	foreach ($all_packages as $pkgid => $package) {
		echo "<h2>".htmlspecialchars($package['Name'])."</h2>";
		$stats = get_stats_for_packageid($pkgid);
		echo "<fieldset>";
		foreach ($stats as $version => $stats) {
			echo '<div>';
			echo '<span>'.htmlspecialchars($version).'</span>';
			echo '<span class="label">'.count($stats).'</span>';
			echo '</div>';
		}
		echo "</fieldset>";
	}
?>

		</div>
	</body>
</html>