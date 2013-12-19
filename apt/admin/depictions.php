<?php
	require_once("functions.php");
	
	if (isset($_POST['json'])) {
		// Le str_replace corrige des problèmes sur 000webhost
		$cfg = json_decode(urldecode($_POST['json']), true);
		if ($cfg && is_array($cfg) && count($cfg) > 0) {
			set_config($cfg, "depiction");
			
			// Juste pour enlever la confirmation de renvoyer le formulaire :/
			header("Refresh: 0");
			die();
		}
	}
?>
<?php html_head('<link href="resources/depictions.css" rel="stylesheet" />'."\n\t".'<script src="resources/depictions_js.php"></script>'); ?>
	<div id="depictions"></div>
	<noscript><?php echo localizehtml("Veuillez activer JavaScript"); ?></noscript>
<?php html_foot(); ?>