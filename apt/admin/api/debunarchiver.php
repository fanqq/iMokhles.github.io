<?php
	/*
	 Vous vous demandez d'où viennent toutes ces valeurs?
	 
	 --> http://en.wikipedia.org/wiki/Ar_(Unix)
	 */
	
	class ArArchiver {
		private function fread_($fp, $l) {
			return preg_replace("/\s*$/", "", fread($fp, $l));
		}
		function get_files_table($file) {
			$fp = fopen($file, "r");
			if (!$fp)
				return null;
			fseek($fp, 8);
			
			$fullsize = filesize($file);
			
			$array = array();
			while (ftell($fp) < $fullsize) {// Si j'utilise !feof($fp) ça fait une boucle infinie :/
				$name =			$this->fread_($fp, 16);
				$timestamp =	$this->fread_($fp, 12);
				$owner =		$this->fread_($fp, 6);
				$group =		$this->fread_($fp, 6);
				$mode =			$this->fread_($fp, 8);
				$size =			$this->fread_($fp, 10);
				fseek($fp, 2, SEEK_CUR);// Magic
				
				fseek($fp, intval($size), SEEK_CUR);
				//$content =		$this->fread_($fp, intval($size));
				
				$array[$name] = array(
								 "name" => $name,
								 "timestamp" => intval($timestamp),
								 "owner" => intval($owner),
								 "group" => intval($group),
								 "mode" => $mode,
								 "size" => intval($size)/*,
								 "content" => $content*/
								 );
			}
			
			fclose($fp);
			
			return $array;
		}
		function get_file($file, $filename) {
			$fp = fopen($file, "r");
			if (!$fp)
				return null;
			fseek($fp, 8);
			
			$fullsize = filesize($file);
			
			$array = array();
			while (ftell($fp) < $fullsize) {// Si j'utilise !feof($fp) ça fait une boucle infinie :/
				$name =			$this->fread_($fp, 16);
				$timestamp =	$this->fread_($fp, 12);
				$owner =		$this->fread_($fp, 6);
				$group =		$this->fread_($fp, 6);
				$mode =			$this->fread_($fp, 8);
				$size =			$this->fread_($fp, 10);
				if ($name != $filename) {
					fseek($fp, intval($size) + 2, SEEK_CUR);
					continue;
				} else {
					fseek($fp, 2, SEEK_CUR);// Magic
					$content =		$this->fread_($fp, intval($size));
					fclose($fp);
					return array(
								 "name" => $name,
								 "timestamp" => intval($timestamp),
								 "owner" => intval($owner),
								 "group" => intval($group),
								 "mode" => $mode,
								 "size" => intval($size),
								 "content" => $content
								 );
				}
			}
			
			fclose($fp);
			return null;
		}
		
		function build_archive($ar, $files) {
			$fp = fopen($ar, "w");
			fwrite($fp, "!<arch>\n");
			foreach ($files as $path) {
				fwrite($fp, str_pad(basename($path), 16));
				fwrite($fp, str_pad(strval(filemtime($path)), 12));
				fwrite($fp, "0     0     100644  ");
				//fwrite($fp, str_pad(substr(sprintf('%o', fileperms($path)), -4), 8));
				fwrite($fp, str_pad(strval(filesize($path)), 10));
				fwrite($fp, "`\n");// Magic
				fwrite($fp, file_get_contents($path));
			}
			fclose($fp);
		}
	}
	
	function untar($file, $dest = "./") {
		if (!is_readable($file)) return false;
		
		$filesize = filesize($file);
		
		// Minimum 4 blocks
		if ($filesize <= 512*4) return false;
		
		if (!preg_match("/\/$/", $dest)) {
			// Force trailing slash
			$dest .= "/";
		}
		
		//Ensure write to destination
		if (!file_exists($dest)) {
			if (!mkdir($dest, 0777, true)) {
				return false;
			}
		}
		
		$fh = fopen($file, 'rb');
		$total = 0;
		while (false !== ($block = fread($fh, 512))) {
			
			$total += 512;
			$meta = array();
			// Extract meta data
			// http://www.mkssoftware.com/docs/man4/tar.4.asp
			$meta['filename'] = trim(substr($block, 0, 99));
			$meta['mode'] = octdec((int)trim(substr($block, 100, 8)));
			$meta['userid'] = octdec(substr($block, 108, 8));
			$meta['groupid'] = octdec(substr($block, 116, 8));
			$meta['filesize'] = octdec(substr($block, 124, 12));
			$meta['mtime'] = octdec(substr($block, 136, 12));
			$meta['header_checksum'] = octdec(substr($block, 148, 8));
			$meta['link_flag'] = octdec(substr($block, 156, 1));
			$meta['linkname'] = trim(substr($block, 157, 99));
			$meta['databytes'] = ($meta['filesize'] + 511) & ~511;
			
			if ($meta['link_flag'] == 5) {
				// Create folder
				if (!file_exists($dest . $meta['filename']))
					mkdir($dest . $meta['filename'], 0777, true);
				chmod($dest . $meta['filename'], $meta['mode']);
			}
			
			if ($meta['databytes'] > 0) {
				$block = fread($fh, $meta['databytes']);
				// Extract data
				$data = substr($block, 0, $meta['filesize']);
				
				// Write data and set permissions
				if (false !== ($ftmp = fopen($dest . $meta['filename'], 'wb'))) {
					fwrite($ftmp, $data);
					fclose($ftmp);
					touch($dest . $meta['filename'], $meta['mtime'], $meta['mtime']);
					
					if ($meta['mode'] == 0744) $meta['mode'] = 0644;
					
					chmod($dest . $meta['filename'], $meta['mode']);
				}
				$total += $meta['databytes'];
			}
			
			if ($total >= $filesize-1024) {
				return true;
			}
		}
	}
?>