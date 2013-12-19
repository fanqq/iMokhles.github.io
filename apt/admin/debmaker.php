<?php
	require_once("functions.php");
	
	$wdir = debmaker_folder();
	
	function delTree($dir) {
		$files = array_diff(scandir($dir), array('.','..'));
		foreach ($files as $file) {
			(is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
		}
		return rmdir($dir);
	}
	
	function get_all_paquets() {
		global $wdir;
		$dirs = glob($wdir."/*/");
		$rep = array();
		foreach ($dirs as $dir) {
			$rep[basename($dir)] = array("nom" => basename($dir));
		}
		return $rep;
	}
	$paquets = get_all_paquets();
	
	if (isset($_POST['Create_paquet']) && isset($_POST['nom_paquet']) && strlen($_POST['nom_paquet']) > 0) {
		$folder = str_replace(' ', '', sanitize_file_name($_POST['nom_paquet']));
		mkdir($wdir."/".$folder);
		file_put_contents($wdir."/".$folder."/control", "Architecture: iphoneos-arm\nName: Paquet\nPackage: com.sourcemanager.?????\nVersion: 1.0\n");
		
		refresh_die();
	}
	
	if (isset($_POST['Supprimer_paquet']) && isset($paquets[$_POST['Supprimer_paquet']])) {
		delTree($wdir."/".$_POST['Supprimer_paquet']);
		
		refresh_die();
	}
	
	if (isset($_GET['edit']) && isset($paquets[$_GET['edit']])) {
		$edit_id = $_GET['edit'];
		$edit_path = $wdir."/".$edit_id;
		
		$control = parse_control(file_get_contents($edit_path."/control"));
		
		if (isset($_POST['Control'])) {
			$control2 = $_POST['Control'];
			$control2str = "";
			foreach ($control2 as $key => $value) {
				$control2str .= $key.": ".$value."\n";
			}
			file_put_contents($edit_path."/control", $control2str);
			
			refresh_die();
		}
	}
	
?>
<?php html_head(); ?>
<?php if (isset($edit_id)): ?>
	<?php echo '<h3 class="headbleu">'.localizehtml("Édition de").' <q>'.htmlspecialchars($edit_id).'</q></h3>'."\n"; ?>
<script>/*<![CDATA[*/

/*]]>*/</script>
	<form method="post">
		<fieldset style="width: 400px; ">
			<legend>control</legend>
			<table class="paquets control">
				<colgroup>
					<col style="width: 40%; " />
					<col style="width: 40%; " />
					<col style="width: 20%; " />
				</colgroup>
				<thead>
					<tr>
						<th>Key</th>
						<th>Value</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
<?php
	foreach ($control as $key => $value) {
		echo '<tr>';
		
		echo '<td>'.htmlspecialchars($key).'</td>';
		echo '<td><input name="Control['.htmlspecialchars($key).']" type="text" value="'.htmlspecialchars($value).'" /></td>';
		echo '<td><button class="smalldelbtn" onclick="this.parentNode.parentNode.parentNode.removeChild(this.parentNode.parentNode);" type="button">X</button></td>';
		
		echo '</tr>';
	}
?>

				</tbody>
			</table>
		</fieldset>
		<p><input type="submit" value="<?php echo localizehtml('Appliquer les modifications'); ?>" /></p>
	</form>
<?php else: ?>
	<p><?php echo localizehtml("Paquets personnalisés"); ?> :</p>
	<form method="post">
		<table class="paquets">
			<colgroup>
				<col style="width: 2em; " />
				<col style="width: auto; " />
				<col style="width: 20%; " />
			</colgroup>
			<thead>
				<tr>
<?php
	$tableths = array("No.", "Nom", "Actions");
	foreach ($tableths as $th) {
		echo "\t\t\t\t\t<th>".localizehtml($th)."</th>\n";
	}
	?>
				</tr>
			</thead>
			<tbody>
<?php
	$i = 1;
	foreach ($paquets as $n => $p) {
		echo '<tr>';
		echo '<td>'.htmlspecialchars($i).'</td>';
		echo '<td><a href="?edit='.urlencode($n).'">'.htmlspecialchars($n).'</a></td>';
		echo '<td><button name="Supprimer_paquet" onclick="return confirm(\''.localizehtml("Voulez-vous vraiment supprimer ce paquet?", 1).'\')" type="submit" value="'.htmlspecialchars($n).'">Supprimer</button></td>';
		echo '</tr>';
		$i++;
	}
?>

			</tbody>
		</table>
	</form>
	<form enctype="multipart/form-data" method="post">
		<fieldset>
			<legend><?php echo localizehtml("Ajouter un paquet"); ?></legend>
			<label><?php echo localizehtml("Se baser sur un .deb (facultatif)"); ?> : <input name="fichier" type="file" /></label><br />
			<label><?php echo localizehtml("Identifiant du paquet"); ?> : <input name="nom_paquet" required="required" type="text" /></label><br />
			<span><button name="Create_paquet" type="submit"><?php echo localizehtml("Créer le paquet"); ?></button></span>
		</fieldset>
	</form>
<?php endif; ?>
<?php html_foot(); ?>