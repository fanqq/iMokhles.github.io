<?php
	$_supplangs = array();
	$_langfiles = glob(api_folder()."/translations/*.txt");
	$_langs = array();
	foreach ($_langfiles as $file) {
		$_langs[] = substr($file, 0, strlen($file) - 4);
	}
	if (function_exists('http_negotiate_language')) {
		$_lang = http_negotiate_language($_langs);
	} else {
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			// break up string into pieces (languages and q factors)
			preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);
			
			if (count($lang_parse[1])) {
				// create a list like "en" => 0.8
				$_langs2 = array_combine($lang_parse[1], $lang_parse[4]);
				$_langs3 = array();
				
				// set default to 1 for any without q factor
				foreach ($_langs2 as $lang => $val) {
					$_langs3[] = array("n" => $lang, "q" => $val);
				}
				
				// sort list based on value
				arsort($_langs2, SORT_NUMERIC);
			}
		}
		if (isset($_langs3) && count($_langs3) > 0)
			$_lang = substr($_langs3[0]["n"], 0, 2);
		else
			$_lang = "en";
	}
	// Get file
	$_lang_file = api_folder()."/translations/".$_lang.".txt";
	if (!file_exists($_lang_file) && $_lang != "fr") {
		$_lang_file = api_folder()."/translations/en.txt";
	}
	// Parse file
	$_localization = array();
	$_loclines = file_exists($_lang_file) ? file($_lang_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : array();
	foreach ($_loclines as $line) {
		if (substr($line, 0, 1) == "#")
			continue;
		preg_match('/^(.*)===(.*)$/', $line, $matches);
		$_localization[$matches[1]] = $matches[2];
	}
	function localize($text) {
		global $_localization;
		return isset($_localization[$text]) ? $_localization[$text] : $text;
	}
	
	// Localization dans le code HTML
	function localizehtml($text, $escapelevel = 0) {
		return str_replace("'", str_repeat("\\", $escapelevel)."'", htmlspecialchars(localize($text)));
	}
?>