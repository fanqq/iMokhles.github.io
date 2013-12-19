<?php
	require_once("functions.php");
	
	if (!isset($_GET['file'])) {
		die();
	}
	
	function not_found($msg = "Not Found") {
		header("HTTP/1.1 404 ".$msg);
	}
	
	$config = get_config();
	$info = $config['info'];
	$file = $_GET['file'];
	
	if (preg_match("/^debs\/[^\/]+\.deb$/", $file)) {
		$path = userdata_folder()."/" . $file;
		if (file_exists($path)) {
			add_stat_for_deb_file($file, $_SERVER);
			
			header("Content-Type: application/x-deb");
			header("Content-Length: " . filesize($path));
			readfile($path);
		} else
			not_found();
		exit;
	}
	
	if (preg_match("/^uploads\/[^\/]+\.png$/", $file)) {
		$path = userdata_folder()."/".$file;
		if (file_exists($path)) {
			header("Content-Type: image/png");
			header("Content-Length: " . filesize($path));
			readfile($path);
		} else
			not_found();
		exit;
	}
	
	if ($file == "CydiaIcon.png") {
		header("Content-Type: image/png");
		if (isset($info['icone'])) {
			readfile(get_uploaded_file_path($info['icone']));
		} else
			readfile("sm_default2.png");
	} else if ($file == "Release") {
		header("Content-Type: text/plain; charset=UTF-8");
		echo "Architectures: iphoneos-arm\n";
		echo "Components: main\n";
		if (isset($info['description']) && strlen($info['description']) > 0)
			echo "Description: ".$info['description']."\n";
		if (isset($info['nom_court']) && strlen($info['nom_court']) > 0)
			echo "Label: ".$info['nom_court']."\n";
		if (isset($info['nom']) && strlen($info['nom']) > 0)
			echo "Origin: ".$info['nom']."\n";
		echo "Version: 0.1\n";
	} else if ($file == "Packages.gz") {
		if (isset($_SERVER['HTTP_X_UNIQUE_ID']))
			update_udid_list($_SERVER['HTTP_X_UNIQUE_ID']);
		header("Content-Type: application/x-gzip");
		echo get_compressed_packages_string();
	} else {
		not_found();
	}
?>