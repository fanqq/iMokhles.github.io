<?php
	error_reporting(-1);
	ini_set('error_reporting', E_ALL);
	ini_set('display_errors', 1);
	
	session_start();
	
	if (!function_exists('json_encode')) {
		require_once("json4all.php");
	}
	
	require_once("api/functions.php");
	
	$_self = $_SERVER['PHP_SELF'];
	$_self = substr($_self, strrpos($_self, '/') + 1);
	
	$onglets = array(
					 "Accueil" => "accueil.php",
					 "Paquets" => "paquets.php",
					 "Catégories" => "categories.php",
					 "Depictions" => "depictions.php",
					 /*"DebMaker" => "debmaker.php", -- Ce n'est pas fini ;) */
					 "Configuration" => "configuration.php"
					 );
	
	if (!file_exists(get_config_path()) && $_self != "config.php") {
		header("Location: config.php");
		die();
	}
	
	$_config = get_config();
	$_logged_in = isset($_SESSION['user']) && isset($_config['user']) && $_SESSION['user'] == $_config['user'];
	if ($_logged_in) {
		if (!in_array($_self, array_values($onglets)) && !in_array($_self, array("ajouterpaquet.php")) && $_self != "404.php") {
			header("Location: accueil.php");
			die();
		}
	} else if ($_self != "login.php" && isset($_config['user']) && $_self != "config.php") {
		header("Location: login.php");
		die();
	}
	
	function size_string($octets) {
		$size = $octets;
		$unit = "Octets";
		if ($size > 1024) {
			$size /= 1024;
			$unit = "Ko";
		}
		if ($size > 1024) {
			$size /= 1024;
			$unit = "Mo";
		}
		if ($size > 1024) {
			$size /= 1024;
			$unit = "Go";
		}
		return round($size, 2) . " " . $unit;
	}
	
	function get_uploads() {
		$files = uploads_folder();
		$uploads = array();
		for ($i = 2; $i < count($files); $i++)
			$uploads[] = $files[$i];
		return $uploads;
	}
	
	function html_head($suphtml = "") {
		global $_self;
		global $_config;
		global $_logged_in;
		global $_lang;
		$lang = isset($_lang) ? $_lang : "fr";
		
		// XHTML5 :)
		if (!isset($_SERVER["HTTP_ACCEPT"]) || stristr($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml")) {
			header("Content-Type: application/xhtml+xml; charset=UTF-8");
			echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?".">\n";
		} else
			header("Content-type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang; ?>">
<head>
	<meta charset="UTF-8" />
	<title><?php echo isset($_config['info']) ? htmlspecialchars($_config['info']['nom']) : "Admin. de la source"; ?></title>
	<meta content="width=700" name="viewport" />
	<link href="resources/style.css" rel="stylesheet" />
	<!--[if lte IE 8]><link href="resources/iehacks.css" rel="stylesheet" /><![endif]-->
<?php if (strlen($suphtml) > 0) echo "\t".$suphtml."\n"; ?>
</head>
<body>
	<div class="barrehaut">
		<div class="barregauche">
<?php if (isset($_config['user'])): ?>
			<img alt="Icône" height="60" src="../CydiaIcon.png" width="59" />
<?php else: /* data:image/png;base64,< ?php echo base64_encode(file_get_contents(api_folder().'/sm_default2.png')); ? > */ ?>
			<img alt="Icône" height="60" src="" width="59" />
<?php endif; ?>
			<h1><?php echo (isset($_config['info']) ? $_config['info']['nom'] : "SourceManager") . (isset($_config['info']) ? ' - '.$_config['info']['description'] : ""); ?></h1>
		</div>
<?php if ($_logged_in): ?>
		<div class="barredroite">
			<a class="deconnecter" href="logout.php"><?php echo htmlspecialchars(localize("Déconnecter")); ?></a>
		</div>
<?php endif; ?>
	</div>
	<ul class="barreonglets">
<?php
	echo "\t\t";// J'aime bien faire "Afficher le code source" sur mes pages :)
	if ($_logged_in) {
		global $onglets;
		foreach ($onglets as $key => $val) {
			// Je n'ai pas besoin de htmlspecialchars() ici, car je contrôle les valeurs (voir ci-dessus)
			echo '<li class="onglet'.($_self==$val?' courant':'').'"><a href="'.htmlspecialchars($val).'">'.htmlspecialchars(localize($key)).'</a></li>';
		}
	} else {
		echo '<li class="onglet"><a>'.htmlspecialchars(localize(isset($_config['user']) ? "Connexion" : "Configuration")).'</a></li>';
	}
?>

	</ul>
	<div class="contenu">
	<!-- Contenu -->
<?php
	}
	function html_foot() {
		echo "\t<!-- /Contenu -->\n\t</div>\n</body>\n</html>";
		exit;// Juste pour 000webhost :)
	}
	
	function numtostr ($num) {
		return number_format($num, 1, ",", " ");
	}
	
	// Bien utile
	function refresh_die($url = null) {
		header("Refresh: 0".($url != null ? ";URL=".$url : ""));
		die();
	}
	
	// Après tout, pourquoi pas :P
	$config = $_config;
?>