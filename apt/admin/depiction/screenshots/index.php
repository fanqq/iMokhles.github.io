<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="UTF-8" />
		<title>Captures d'écran</title>
		<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0" />
		<link href="http://philippe97.ca/css/iphonecss/iphone.css" rel="stylesheet" />
		<script src="http://philippe97.ca/css/iphonecss/iphone.js"></script>
		<link href="style.css" type="text/css" rel="stylesheet" />
		<script src="iscroll.js"></script>
		<script>
			function scrollEnd() {
				var e = document.getElementsByClassName("screenswrapper")[0];
				if (e.offsetTop !== undefined && window.innerHeight <= e.offsetHeight + 10)
					window.scrollTo(0, e.offsetTop - 2.5);
				document.querySelector("#indicator > .active").className = null;
				document.querySelector("#indicator > :nth-child(" + (this.currPageX + 1) + ")").className = "active";
			}
		</script>
	</head>
	<body class="pinstripe">
		<div class="contenu">
<?php
	require_once("../../api/functions.php");
	$packages = get_packages_array();
	
	if (!isset($_GET['package'])) {
		echo "L'identifiant du paquet n'a pas été fournit.";
	} else if (isset($packages[$_GET['package']])) {
		
		$pkgid = $_GET['package'];
		
		$pkg = $packages[$pkgid];
		$config = get_config("overrides");
		$screens = $config[$pkgid]['screenshots'];
		
		
		echo "<fieldset style=\"margin-bottom: .5em; \"><div style=\"background-color: #def; \"><p>Captures d'écran (".count($screens).")<br />".$pkg['Name']." (v".$pkg['Version'].")</p></div></fieldset>";
		
		echo "<div class=\"screenswrapper\"><div id=\"screenshots\"><ul style=\"width: " . count($screens) * 232 . "px; \">";
		
		foreach ($screens as $screen)
			echo "<li><img src=\"" . get_uploaded_file_url($screen) . "\" /></li>";
		echo "</ul></div>";
		
		echo "<ul id=\"indicator\"><li class=\"active\"></li>";
		for ($i = 1; $i < count($screens); $i++)
			echo "<li></li>";
		echo "</ul></div>";
		
		echo "<script>var myScroll=new iScroll('screenshots',{snap:true,momentum:false,vScroll:false,hScroll:true,hScrollbar:false,vScrollbar:false,onScrollEnd:scrollEnd});</script>";
	}
?>

		</article>
	</body>
</html>