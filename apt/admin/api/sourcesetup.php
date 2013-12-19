<?php
	//require_once("functions.php");
	
	if (!function_exists('api_folder')) {
		function api_folder() {
			return dirname(__FILE__);
		}
		function admin_folder() {
			return realpath(api_folder()."/..");
		}
		function userdata_folder() {
			return realpath(admin_folder()."/../userdata");
		}
	}
	
	file_put_contents(admin_folder()."/../.htaccess", "RewriteEngine on\nRewriteBase ".$basepath."\nRewriteRule ^(Packages.*|Release.*|.+\.deb|CydiaIcon\.png|uploads\/[^\/]+|[a-z]{2}(_[A-Z]{2})?\.bz2)$ admin/api/rewrite.php?file=$1\nRewriteRule ^(debs|depiction|stats)\\/?(.*)$ admin/$1/$2\nIndexIgnore *\nphp_value auto_append_file none\nphp_value upload_max_filesize 200M\nphp_value post_max_size 200M");
	file_put_contents(admin_folder()."/.htaccess", "ErrorDocument 404 ".$basepath."/admin/404.php");
	file_put_contents(api_folder()."/.htaccess", "order allow,deny\ndeny from all\n<FilesMatch \"^rewrite.php$\">\nallow from all\n</FilesMatch>");
	file_put_contents(userdata_folder()."/.htaccess", "order allow,deny\ndeny from all");
?>