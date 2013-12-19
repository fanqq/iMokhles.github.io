<?php
	define("SOURCEMANAGER_VERSION", "0.1b7");
	
	// Pas trop super, mais c'est la façon la plus sûre d'avoir un bon accès au fichiers
	function api_folder() {
		return dirname(__FILE__);
	}
	function admin_folder() {
		return realpath(api_folder()."/..");
	}
	function userdata_folder() {
		return realpath(admin_folder()."/../userdata");
	}
	function config_folder() {
		return userdata_folder()."/config";
	}
	function debs_folder() {
		return userdata_folder()."/debs";
	}
	function uploads_folder() {
		return userdata_folder()."/uploads";
	}
	function cache_folder() {
		return userdata_folder()."/cache";
	}
	function tmp_folder() {
		return userdata_folder()."/tmp";
	}
	function stats_folder() {
		return userdata_folder()."/stats";
	}
	function repoicons_folder() {
		return userdata_folder()."/repoicons";
	}
	function debmaker_folder() {
		return userdata_folder()."/debmaker";
	}
	
	// Localization
	include "localization.php";
	
	require_once(api_folder()."/debunarchiver.php");
	
	if (!function_exists('json_encode')) {
		require_once(admin_folder()."/json4all.php");
	}
	if (!function_exists('gzdecode')) {
		function gzdecode($data) {
			return gzinflate(substr($data, 10, -8));
		}
	}
	function is_shell_exec_available() {
		if ($safe_mode = ini_get('safe_mode') && strtolower($safe_mode) != 'off')
			return false;
		if (in_array('shell_exec', array_map('trim', explode(',', ini_get('disable_functions')))))
			return false;
		return true;
	}
	if (!is_shell_exec_available()) {
		require_once(api_folder()."/Tar.php");
	}
	
	function get_config_path($file = "config") {
		return config_folder()."/" . $file . ".json";
	}
	function get_config($file = "config") {
		$path = get_config_path($file);
		if (!file_exists($path))
			return array();
		return json_decode(file_get_contents($path), true);
	}
	function set_config($cfg, $file = "config") {
		//chmod(get_config_path($file), 0664);
		return file_put_contents(get_config_path($file), str_replace("\\\\'", "\\u0027", json_encode($cfg)));
	}
	function delete_config($file = "config") {
		unlink(get_config_path($file));
	}
	
	/* Depictions par défaut {{{ */
	
	if (count(get_config("depiction")) == 0) {
		set_config(array(
						 array("type" => "header", "content" => "Description"),
						 array("type" => "section", "content" => array(
																		array("type" => "description"),
																		array("type" => "screenshots"),
																		)),
						 array("type" => "header", "content" => "Statistiques"),
						 array("type" => "section", "content" => array(
																		array("type" => "downloads"),
																		))
				   ), "depiction");
	}
	
	/* }}} */
	
	function get_uploaded_file_path($file) {
		return uploads_folder()."/" . $file;
	}
	function get_uploaded_file_url($file) {
		$c = get_config();
		return $c['info']['url'] . "uploads/".$file;
	}
	
	function sanitize_file_name($filename) {
		return preg_replace("/[^a-z0-9\._-\s']+/i", "", $filename);
	}
	function rrmdir($dir) {
		$files = glob($dir . '/*');
		if ($files === false) return;
		foreach($files as $file) {
			if(is_dir($file)) rrmdir($file); else unlink($file);
		} rmdir($dir);
	}
	function parse_size_shorthand($value) {
		if (is_numeric($value)) {
			return $value;
		} else {
			$value_length = strlen( $value );
			$qty = substr( $value, 0, $value_length - 1 );
			$unit = strtolower( substr( $value, $value_length - 1 ) );
			switch ( $unit ) {
				case 'k':
					$qty *= 1024;
					break;
				case 'm':
					$qty *= 1048576;
					break;
				case 'g':
					$qty *= 1073741824;
					break;
			}
			return $qty;
		}
	}
	
	function extract_control_of_deb($path) {
		if (is_shell_exec_available())
			return shell_exec("ar -p ".escapeshellarg($path)." control.tar.gz | tar zxfO - ./control");
		
		// Pour les autres :)
		$path = realpath($path);
		$tarpath = tmp_folder()."/controls/control.tar";
		if (file_exists(dirname($tarpath)))
			rrmdir(dirname($tarpath));
		mkdir(dirname($tarpath), 0777, true);
		$a = new ArArchiver();
		$controlinfo = $a->get_file($path, "control.tar.gz");
		file_put_contents($tarpath, gzdecode($controlinfo['content']));
		//untar($tarpath, dirname($tarpath));
		$tar = new Archive_Tar($tarpath);
		$tar->extract(dirname($tarpath));
		return file_get_contents(dirname($tarpath) . "/control");
	}
	function parse_control($control) {
		$lines = explode("\n", $control);
		$ret = array();
		foreach ($lines as $line) {
			$key = substr($line, 0, strpos($line, ":"));
			$value = substr($line, strpos($line, ":") + 2);
			if (strlen($key) > 0 && strlen($value) > 0)
				$ret[$key] = $value;
		}
		return $ret;
	}
	function control_of_deb($deb) {
		return parse_control(extract_control_of_deb($deb));
	}
	
	function api_sort_names($a, $b) {
		return strcasecmp($a['Name'], $b['Name']);
	}
	function generate_packages_array() {
		$config = get_config();
		$overrides = get_config("overrides");
		$debs_path = debs_folder()."/";
		$debs = scandir($debs_path);
		
		$packages = array();
		
		foreach ($debs as $deb) {
			if ($deb == '.' || $deb == '..')
				continue;
			
			$path = $debs_path . $deb;
			
			$control = control_of_deb($path);
			if (!isset($control['Package']))
				continue;
			
			$control['Filename'] = "./debs/".$deb;
			$control['Size'] = filesize($path);
			$control['MD5sum'] = hash_file("md5", $path);
			$control['SHA1'] = hash_file("sha1", $path);
			$control['SHA256'] = hash_file("sha256", $path);
			
			$control['Depiction'] = $config['info']['url'] . "depiction/?package=" . $control['Package'];
			
			if (isset($overrides[$control['Package']]) && isset($overrides[$control['Package']]['Section']))
				$control['Section'] = $overrides[$control['Package']]['Section'];
			
			ksort($control);
			
			$id = $control['Package'];
			if (!isset($packages[$id]) || version_compare($packages[$id]['Version'], $control['Version']) == -1)
				$packages[$id] = $control;
		}
		
		usort($packages, "api_sort_names");
		
		// On remet les clées enlevées par usort
		$newpkgs = array();
		foreach ($packages as $pkg) {
			$newpkgs[$pkg['Package']] = $pkg;
		}
		return $newpkgs;
	}
	function generate_packages_string($carray = null) {
		$array = $carray != null ? $carray : generate_packages_array();
		
		$p = "";
		foreach ($array as $item) {
			foreach ($item as $key => $value) {
				$p .= ucwords($key) . ": " . $value . "\n";
			}
			$p .= "\n";
		}
		return $p;
	}
	function parse_packages_string($str) {
		$parts = explode("\n\n", $str);
		$ret = array();
		foreach ($parts as $part) {
			if (preg_match("/^Package:\s/", $part))
				$ret[] = parse_control($part);
		}
		return $ret;
	}
	
	function get_packages_array() {
		$path = cache_folder()."/packages.json";
		if (file_exists($path))
			return json_decode(file_get_contents($path), true);
		return array();
	}
	function get_package_for_identifier($id) {
		$pkgs = get_packages_array();
		if (isset($pkgs[$id]))
			return $pkgs[$id];
		return false;
	}
	function get_packages_string() {
		$path = cache_folder()."/Packages";
		if (file_exists($path))
			return file_get_contents($path);
		return "";
	}
	function get_compressed_packages_string() {
		$path = cache_folder()."/Packages.gz";
		if (file_exists($path))
			return file_get_contents($path);
		else
			return gzencode("");
	}
	
	function get_package_for_deb($deb) {
		$pkgs = get_packages_array();
		foreach ($pkgs as $pkg) {
			if (strpos($pkg['Filename'], $deb) !== false)
				return $pkg;
		}
		return null;
	}
	
	function update_packages_cache() {
		$a = generate_packages_array();
		file_put_contents(cache_folder()."/packages.json", json_encode($a));
		
		$p = generate_packages_string($a);
		file_put_contents(cache_folder()."/Packages", $p);
		file_put_contents(cache_folder()."/Packages.gz", gzencode($p));
	}
	
	function add_stat_for_deb_file($deb, $server) {
		if (!isset($_SERVER['HTTP_X_UNIQUE_ID']))
			return;
		
		$stats_folder = stats_folder();
		$pkg = get_package_for_deb($deb);
		if (!$pkg)
			return;
		
		$folder = $stats_folder."/".$pkg['Package'];
		if (!file_exists($folder))
			mkdir($folder);
		$file = $folder."/".$pkg['Version'];
		
		chmod($file, 0775);
		$fp = fopen($file, "a");
		fwrite($fp, $_SERVER['HTTP_X_UNIQUE_ID']."___".$_SERVER['HTTP_X_MACHINE']."___".$_SERVER['HTTP_X_FIRMWARE']."___".time()."\n");
		fclose($fp);
	}
	
	function api_stats_sort_versions($a, $b) {
		//return version_compare($a, $b);
		return version_compare(str_replace('-', '.0.', $a), str_replace('-', '.0.', $b));
	}
	function get_stats_for_packageid($pkgid, $onlyunique = false) {
		$folder = stats_folder()."/".$pkgid;
		if (!file_exists($folder)) {
			$pkgs = get_packages_array();
			return array($pkgs[$pkgid]['Version'] => array());
		}
		$files = scandir($folder);
		array_splice($files, 0, 2);// Retirer . et ..
		usort($files, "api_stats_sort_versions");
		
		$ret = array();
		foreach ($files as $file) {
			$ret[$file] = file($folder."/".$file, FILE_IGNORE_NEW_LINES);
		}
		return $ret;
	}
	
	function update_udid_list($add) {
		$path = stats_folder()."/all_users.txt";
		if (!file_exists($path))
			touch($path);
		chmod($path, 0775);
		
		if (in_array($add, file($path, FILE_IGNORE_NEW_LINES)))
			return;
		
		$fp = fopen($path, "a");
		fwrite($fp, $add."\n");
		fclose($fp);
	}
	function get_all_udid_list() {
		$path = stats_folder()."/all_users.txt";
		$file = file($path, FILE_IGNORE_NEW_LINES);
		if ($file === false)
			return array();
		else
			return $file;
	}
	
	function cat_create_deb_folder() {
		$folder = repoicons_folder();
		if (is_dir($folder))
			rrmdir($folder);
		mkdir($folder, 0777);
		
		mkdir($folder."/Applications/Cydia.app/Sections", 0777, true);
	}
	
	function cat_build_package_repoicons_with_info($pkginfo) {
		$folder = repoicons_folder();
		
		$control = "Architecture: iphoneos-arm\nDescription: Les icônes des catégories de la source.\nEssential: yes\nName: ".$pkginfo['nom']."\nPackage: ".$pkginfo['id']."\nVersion: ".$pkginfo['version']."\n";
		file_put_contents($folder."/control", $control);
		
		if (is_dir($folder."/deb"))
			rrmdir($folder."/deb");
		mkdir($folder."/deb", 0777);
		
		$shell_exec_avail = is_shell_exec_available();
		
		chdir($folder);
		if ($shell_exec_avail) {
			shell_exec("tar -czf deb/control.tar.gz ./control");
			shell_exec("tar -czf deb/data.tar.gz ./Applications");
		} else {
			$controltar = "deb/control.tar";
			$con = new Archive_Tar($controltar);
			$con->create(array("./control"));
			if (file_exists($controltar))
				file_put_contents($controltar.".gz", gzencode(file_get_contents($controltar)));
			
			$datatar = "deb/data.tar";
			$dat = new Archive_Tar($datatar);
			$datafiles = glob("./Applications/Cydia.app/Sections/*");
			$dat->create($datafiles ? $datafiles : array());
			if (file_exists($datatar))
				file_put_contents($datatar.".gz", gzencode(file_get_contents($datatar)));
		}
		chdir("deb");
		file_put_contents("debian-binary", "2.0\n");
		if ($shell_exec_avail)
			shell_exec("ar -rcv repoicons.deb debian-binary control.tar.gz data.tar.gz");
		else {
			$a = new ArArchiver();
			$a->build_archive("repoicons.deb", array("debian-binary", "control.tar.gz", "data.tar.gz"));
		}
		
		$debpath = $folder."/deb/repoicons.deb";
		$newdebpath = debs_folder()."/repoicons.deb";
		if (file_exists($newdebpath))
			unlink($newdebpath);
		if (file_exists($debpath))
			copy($debpath, $newdebpath);
		
		update_packages_cache();
	}
	
	function cat_get_all_categories() {
		$folder = repoicons_folder()."/Applications/Cydia.app/Sections";
		
		if (!is_dir($folder))
			return array();
		$files = scandir($folder);
		$images = array();
		foreach ($files as $file) {
			if ($file == '.' || $file == '..' || preg_match('/\.png$/', $file) != true)
				continue;
			$name = preg_replace('/\.png$/i', '', str_replace('_', ' ', $file));
			$images[$name] = $folder."/".$file;
		}
		return $images;
	}
?>