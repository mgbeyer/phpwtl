<?php
namespace phpWTL\LogWriter\FLW;

/**
  * Static helper class for FileLogWriter (FLW). 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.6
  * @api
  */
class FileLogWriterHelper {
	/** Separator character for paths. */
	const FOLDER_SEPARATOR= "/";
	/** Line terminator for Windows. */
	const EOL_WIN= "\r\n";
	/** Line terminator for Linux. */
	const EOL_UNIX= "\n";
	/** CSV default field delimiter. */
	const CSV_FIELD_DELIMITER= ",";
	/** CSV default field quote. */
	const CSV_FIELD_QUOTE= '"';
	/** CSV default field quote escape. */
	const CSV_FIELD_QUOTE_ESCAPE= '"';

	/** 
	  * APR1-MD5 encryption method (Windows compatible).
	  *
	  * @param string $plainpasswd The password to encrypt in plain text.
	  * @return string Encrypted password (hash).
	  *
	  * @author http://designedbywaldo.com/en/tools/password-hash
	  * @version v1.0.0
	  * @api
	  */
	static public function cryptApr1Md5($plainpasswd) {
		$tmp= null;
		$salt= substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8);
		$len= strlen($plainpasswd);
		$text= $plainpasswd.'$apr1$'.$salt;
		$bin= pack("H32", md5($plainpasswd.$salt.$plainpasswd));
		for($i= $len; $i > 0; $i -= 16) { $text.= substr($bin, 0, min(16, $i)); }
		for($i= $len; $i > 0; $i >>= 1) { $text.= ($i & 1) ? chr(0) : $plainpasswd{0}; }
		$bin= pack("H32", md5($text));
		for($i= 0; $i < 1000; $i++) {
			$new= ($i & 1) ? $plainpasswd : $bin;
			if ($i % 3) $new.= $salt;
			if ($i % 7) $new.= $plainpasswd;
			$new.= ($i & 1) ? $bin : $plainpasswd;
			$bin= pack("H32", md5($new));
		}
		for ($i= 0; $i < 5; $i++) {
			$k= $i + 6;
			$j= $i + 12;
			if ($j == 16) $j= 5;
			$tmp= $bin[$i].$bin[$k].$bin[$j].$tmp;
		}
		$tmp= chr(0).chr(0).$bin[11].$tmp;
		$tmp= strtr(strrev(substr(base64_encode($tmp), 2)),
		"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
		"./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz");
	 
		return "$"."apr1"."$".$salt."$".$tmp;
	}

	/** 
	  * Method handling file write operation.
	  *
	  * @param string $path File/path to write to
	  * @param string $content String to write
	  * @param string $mode Write mode (fopen), "w" or "a", default "w"
	  * @return boolean true if successful
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.2.0
	  * @api
	  */
	static public function writeToFile($path, $content, $mode= "w") {
		$ret= true;
		
		switch ($mode) {
			case "w":
			break;
			case "a":
			break;
			default:
				$mode= "w";
			break;
		}
		if  (!$handle= @fopen($path, $mode)) {
			$ret= false;
		} else {			
			if (@fwrite($handle, $content) === FALSE) {
				$ret= false;
			}
			if ($handle) @fclose($handle);
		}	
		
		return $ret;
	}

	/** 
	  * Simple filename sanitizer. Purges characters other than alphanumeric, hyphen, underscore and dot.
	  *
	  * @param string $name
	  * @return string The sanitized string.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
	static public function sanitizeFilename($name) {
		$pat= "/[^a-zA-Z0-9-_.]/";
		return preg_replace($pat, "", $name);
	}
	
	/** 
	  * File path sanitizer:
	  *
	  * - Convert all separator characters for paths to FOLDER_SEPARATOR
	  * - Correct separator count
	  * - Correct dot count
	  * - Whitelist permitted characters, OS dependent (sift out everything else). For Windows: Alphanumeric, hyphen, underscore, colon, dot, space, German umlauts. For Linux or any other OS: Alphanumeric, hyphen, underscore, tilde, dot.
	  * - Harmonize trailing directory separator (make sure one is in place regardless of original path)
	  *
	  * @param string $path
	  * @return string The sanitized path string.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
	static public function sanitizePath($path) {
		// correct directory separator
		$path= str_replace("\\", self::FOLDER_SEPARATOR, $path);
		// correct separator count
		$pat= "/\\".self::FOLDER_SEPARATOR."{2,}/";
		$path= preg_replace($pat, self::FOLDER_SEPARATOR, $path);
		// correct dot count
		$pat= "/\\".self::FOLDER_SEPARATOR."\.{3,}\\".self::FOLDER_SEPARATOR."/";		// both prefix + suffix
		$path= preg_replace($pat, "/", $path);
		$pat= "/\.{3,}\\".self::FOLDER_SEPARATOR."/";		// suffix
		$path= preg_replace($pat, "", $path);
		$pat= "/\\".self::FOLDER_SEPARATOR."\.{3,}/";		// prefix
		$path= preg_replace($pat, "", $path);

		// whitelist permitted characters, os dependent (sift out everything else)
		if (self::isWin()) {
			$pat= "/[^a-zA-Z0-9-_:. ÄÖÜäöüß\\".self::FOLDER_SEPARATOR."]/";
		} else {
			$pat= "/[^a-zA-Z0-9-_~.\\".self::FOLDER_SEPARATOR."]/";
		}	
		$path= preg_replace($pat, "", $path);

		// harmonize trailing directory separator (make sure one is in place regardless of original path)
		$path= self::pathHelperHarmonizeTrailingSeparator($path);
		
		return $path;
	}

	/** 
	  * Make sure a given path does at no time leave or become equal to another path (aka "the document root"):
	  *
	  * - Ensure that the first N characters of $path are exactly the same as $root, reject up-front if not
	  * - If true, allow for OS agnostic level search (eg. snip away leading Win C:)
	  * - The first time $path goes above $root, reject it
	  * - If $path ends up at the same level as $root, reject it
	  *
	  * @param string $path The path to validate
	  * @param string $root The path representing document root
	  * @return boolean
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
	static public function pathLeavesOrEqualsRoot($path, $root) {	
		$ret= false;
		
		$path= rtrim($root, self::FOLDER_SEPARATOR).$path;
		
		// Ensure that the first N characters of $path are exactly the same as $root, reject up-front if not
		if (self::strStartsWith($path, $root)) {
		
			// OS agnostic absolute path
			if (preg_match('/[A-Za-z]{1}:/', $path, $cut)>0) $path= substr($path, strlen($cut[0]));
			if (preg_match('/[A-Za-z]{1}:/', $root, $cut)>0) $root= substr($root, strlen($cut[0]));
			$threshold= self::pathDepth($root);
			$pointer= $threshold;
			foreach (explode(self::FOLDER_SEPARATOR, $path) as $k=>$p) {
				switch ($p) {
					case ".":
					case "":
					break;
					case "..":
						$pointer--;
					break;
					default:
						$pointer++;
					break;
				}
				// first time path goes above document root level, reject it (potential security issue!)
				if ($pointer<$threshold) {
					$ret= true;				
				}
			}
			// if path ends up at same level as document root, reject it (we don't want .htaccess to interfere with the document root!)		 
			if ($pointer==$threshold) $ret= true;
		}
		
		return $ret;
	}


	/** 
	  * Cut-off leading directory separator.
	  *
	  * @param string $path
	  * @return string
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	// todo: omit or rework, see above
	static public function pathHelperCutLeadingSeparator($path) {
		return ltrim($path, self::FOLDER_SEPARATOR);
	}

	/** 
	  * Harmonize trailing directory separator (make sure one is in place regardless of original path).
	  *
	  * @param string $path
	  * @return string
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	static public function pathHelperHarmonizeTrailingSeparator($path) {
		return rtrim($path, self::FOLDER_SEPARATOR).self::FOLDER_SEPARATOR;
	}

	/** 
	  * Calculate the depth (i.e. number of portions divided by FOLDER_SEPARATOR) of a given path.
	  *
	  * @param string $path
	  * @return int
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	static public function pathDepth($path) {
		return count(explode(self::FOLDER_SEPARATOR, $path))-1;
	}

	/** 
	  * Check if OS running the script is Win or Linux.
	  *
	  * @return boolean
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	static public function isWin() {
		return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
	}

	/** 
	  * Return OS specific line terminator.
	  *
	  * @return string
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	static public function getOsEol() {
		return self::isWin() ? self::EOL_WIN : self::EOL_UNIX;
	}

	/**
	  * CSV representation of the given content.
	  * @param array $content Associative array containing all fields to write, field names as keys, field content as values (representing a logfile entry).
	  * @param array $params CSV parameter: "field_delimiter" (default -> ,), "field_quote" (default -> "), "field_quote_escape" (default -> ").  
	  * @return array Associative, "field_names" -> csv string of field IDs, "field_content" -> csv string of log field contents
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	static public function content2CSV($content, $params= null) {
		$ret= array("field_names" => array(), "field_content" => array());
		
		if ($content) {
			
			$f_delimiter= static::CSV_FIELD_DELIMITER;
			$f_quote= static::CSV_FIELD_QUOTE;
			$f_quote_escape= static::CSV_FIELD_QUOTE_ESCAPE;
			if ($params && is_array($params)) {
				if (array_key_exists("field_delimiter", $params)) $f_delimiter= $params["field_delimiter"];
				if (array_key_exists("field_quote", $params)) $f_quote= $params["field_quote"];
				if (array_key_exists("field_quote_escape", $params)) $f_quote_escape= $params["field_quote_escape"];
			}
			
			$keys= array();
			$values= array();
			foreach ($content as $k=>$v) {
				array_push($keys, $f_quote.str_replace($f_quote, $f_quote_escape.$f_quote, $k).$f_quote);
				array_push($values, $f_quote.str_replace($f_quote, $f_quote_escape.$f_quote, $v).$f_quote);
			}
			
			$ret["field_names"]= implode($f_delimiter, array_values($keys));
			$ret["field_content"]= implode($f_delimiter, array_values($values));
		}
				
		return $ret;
	}

	/** 
	  * Simple check if given path is absolute (leading folder separator is present or not).
	  *
	  * @param string $target Path to check.
	  * @return boolean True if path is absolute, false if not.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
	static function isAbsolutePath($target) {
			return (substr($target, 0, 1) == self::FOLDER_SEPARATOR  ? true : false);
	}

	/** 
	  * Separate given $target into path and filename.
	  *
	  * @param string $target Path + filename to separate.
	  * @return array Assoc. array, "pathname" contains path, "filename" contains file portion.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
	static function separatePathAndFile($target) {
		$pathname= str_replace(basename($target), "", $target);
		$filename= str_replace($pathname, "", $target);
		return array('pathname' => $pathname, 'filename' => $filename);
	}

	/** 
	  * Clear a given $target path of single dots, empty portions between folder separators
	  * and purify double dot parts
	  *
	  * @param string $target Path to cleanup
	  * @return string Purified path.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
	static function cleanupPath($target) {
		$final_path= "";

		$token= explode(self::FOLDER_SEPARATOR, $target);
		
		// delete all occurences of /./ and empty segments
		$newtoken= array();
		foreach($token as $v) {
			if ($v != "." && $v != "") $newtoken[]= $v;	
		}
		$token= $newtoken;
		// handle /../ segments
		$series_count= 0;
		$series_start= -1;
		foreach($token as $k => $v) {
			if ($v == "..") {
				$token[$k]= "";
				if ($series_start == -1) $series_start= $k;
				$series_count++;
			} 
			if ($v != ".." || $k == count($token)-1) {
				for ($i=0;$i<$series_count;$i++) {
					$idx= $series_start-$i-1;
					if ($idx>=0) $token[$idx]= "";
				}
				$series_count= 0;
				$series_start= -1;
			}
		}

		// build final path string
		foreach($token as $v) {
			if ($v != "") $final_path= $final_path.$v.self::FOLDER_SEPARATOR;
		}
		
		return $final_path;
	}

	
	private static function strStartsWith($haystack, $needle) {
		 $length= strlen($needle);
		 return (substr($haystack, 0, $length) === $needle);
	}

	private static function strEndsWith($haystack, $needle) {
		$length= strlen($needle);
		if ($length == 0) return true;
		return (substr($haystack, -$length) === $needle);
	}

}
?>