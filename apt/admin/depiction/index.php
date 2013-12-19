<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="UTF-8" />
		<title>Depiction</title>
		<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0" name="viewport" />
		<link href="http://philippe97.ca/css/iphonecss/iphone.css" rel="stylesheet" />
		<script src="http://philippe97.ca/css/iphonecss/iphone.js"></script>
	</head>
	<body class="pinstripe">
		<div class="contenu">
<?php
	require_once("../api/functions.php");
	
	$config = get_config("depiction");
	
	$packages = get_packages_array();
	if (isset($_POST['customjson'])) {
		$config = json_decode(urldecode($_POST['customjson']), true);
		$pkgid = "com.vous.paquet";
		$package = array("Name" => localizehtml("Faux paquet"), "Package" => $pkgid, "Description" => localizehtml("Description exemple."));
	} else if (isset($_GET['package']) && isset($packages[$_GET['package']])) {
		$pkgid = $_GET['package'];
		$package = $packages[$pkgid];
	} else {
		$pkgid = "inconnu";
		$package = array("Package" => "inconnu", "Description" => "<span style=\"color: crimson; font-weight: bold; \">Aucun identifiant de paquet valide fourni.</span>");
	}
	
	$overrides = get_config("overrides");
	$override = isset($_POST['customjson']) ? array("screenshots" => array()) : (isset($overrides[$pkgid]) ? $overrides[$pkgid] : array());
	
	function filtercontent($c, $pkg = array(), $html = false) {
		$content = $c;
		if (preg_match_all('/___[a-z]+___/i', $content, $matches)) {
			$matches = array_unique($matches[0]);
			foreach ($matches as $match) {
				$key = substr($match, 3, strlen($match) - 6);
				$content = str_replace($match, isset($pkg[$key]) ? $pkg[$key] : "", $content);
			}
		}
		return $html ? $content : htmlspecialchars($content);
	}
	
	foreach ($config as $part) {
		switch ($part['type']) {
			case "header":
				echo "<h2>" . filtercontent($part['content'], $package) . "</h2>";
				break;
				
			case "footer":
				echo "<p>" . filtercontent($part['content'], $package) . "</p>";
				break;
				
			case "section":
				echo "<fieldset>";
				foreach ($part['content'] as $cell) {
					if ($cell['type'] == "description")
						echo "<div><p>" . filtercontent(isset($override['Description']) ? $override['Description'] : $package['Description'], $package, true) . "</p></div>";
					else if ($cell['type'] == "screenshots" && isset($override['screenshots']))
						echo "<a href=\"screenshots/?package=" . urlencode($pkgid) . "\" target=\"_blank\"><img class=\"icon\" src=\"screenshots.png\" /><span>".localizehtml("Captures d'écran")."</span><span class=\"label\">".count($override['screenshots'])."</span></a>";
					else if ($cell['type'] == "downloads")
						echo "<a href=\"stats/?package=" . urlencode($pkgid) . "\" target=\"_blank\"><span>".localizehtml("Téléchargements")."</span></a>";
					else if ($cell['type'] == "cell") {
						/*echo "<div";
						if ($cell['mode'] == "dotted")
							echo " data-cell=\"dotted\"";
						else if ($cell['mode'] == "text")
							echo " data-cell=\"onlytext\"";
						if ($cell['chevron'] === false)
							echo " data-accessory=\"none\"";
						if ($cell['href'])
							echo " data-href=\"" . filtercontent($cell['href'], $package) . "\"";/-* XXXXXX: Je sais pas comment on sécurise ça :/ *-/
						if ($cell['selection'] != "default")
							echo " data-selection=\"" . $cell['selection'] . "\"";
						echo ">";
						if ($cell['mode'] == "default") {
							if (isset($cell['icone']) && $cell['icone'] != "default")
								echo "<img class=\"icon\" src=\"" . get_uploaded_file_url($cell['icone']) . "\" />";
							echo "<span>" . filtercontent($cell['titre'], $package) . "</span>";
							if (strlen($cell['valeur']))
								echo "<span>" . filtercontent($cell['valeur'], $package) . "</span>";
						} else
							echo "<p>" . filtercontent($cell['titre'], $package, true) . "</p>";
						echo "</div>";*/
						
						$info = $cell['content'];
						
						echo '<a';
						echo ' href="' . htmlspecialchars($info['url']) . '"';
						if ($info['selection'] != "default")
							echo ' data-selection="' . $info['selection'] . '"';
						echo ' target="_blank">';
						if (isset($info['icone']))
							echo '<img class="icon" src="' . $info['icone'] . '" />';
						echo '<span>' . filtercontent($info['titre'], $package) . '</span>';
						if (strlen($info['valeur']))
							echo '<span class="label">' . filtercontent($info['valeur'], $package) . '</span>';
						echo '</a>';
					}
				}
				echo "</fieldset>";
				break;
				
			default:
				break;
		}
	}
?>

		</div>
	</body>
</html>