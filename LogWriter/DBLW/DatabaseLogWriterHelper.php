<?php
namespace phpWTL\LogWriter\DBLW;
use phpWTL\LogWriter\FLW\FileLogWriterHelper;

require '/../FLW/FileLogWriterHelper.php';

/**
  * Static helper class for DatabaseLogWriter (DBLW). 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.5
  * @api
  */
class DatabaseLogWriterHelper {
	/** Safety level for schema diff operations, no safety */
	const SAFETY_NONE = 100;
	const SAFETY_OFF = 100;
	/** Safety level for schema diff operations, maximum safety */
	const SAFETY_ALL = 110;
	const SAFETY_MAX = 110;
	/** Safety level for schema diff operations, drop field safety */
	const SAFETY_DROP = 101;
	/** Safety level for schema diff operations, change field safety */
	const SAFETY_CHANGE = 102;
	/** Strategy for safe naming of database tables and columns: DBAL escaping via "quoteIdentifier" */
	const SAFE_NAMING_STRATEGY_DBAL_ESCAPING = 100;
	/** Strategy for safe naming of database tables and columns: phpWTL internal character filter */
	const SAFE_NAMING_STRATEGY_WTL_CLEANSING = 101;

	/** 
	  * Read credentials ini file and return a connection params array.
	  *
	  * @param boolean $handle_htaccess Create/update .htaccess to protect credentials file.
	  * @param string $filename
	  * @return array
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.2
	  * @api
	  */
	static public function getConnectionParamsFromIni($handle_htaccess, $filename= null) {		
		$ret= null;
		
		$filename= self::prepareIniPath($filename);
		if (!$filename || $filename=="") {					
			$filename= __DIR__.FileLogWriterHelper::FOLDER_SEPARATOR.DatabaseLogWriter::CONN_PARAM_DEFAULT_INI;
		}
				
		if (file_exists($filename)) {
			if ($handle_htaccess) {
				$ok= static::prepareHtaccessProtection($filename);
			} else {
				$ok= true;
			}
			if ($ok) $ret= parse_ini_file($filename);
		}
		
		return $ret;
	}

	/** 
	  * Create/update .htacces to protect credentials file.
	  *
	  * @param string $filename
	  * @param boolean $overwrite true=overwrite whole .htaccess file, false=append (if entry is not already there)
	  * @return boolean true if successful
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.3
	  * @api
	  */
	static public function prepareHtaccessProtection($filename, $overwrite= false) {
		$ret= true;
		
		if ($filename!="") {					
			$pass= true;
			$parts= pathinfo($filename);
			$saveLocation= FileLogWriterHelper::FOLDER_SEPARATOR.FileLogWriterHelper::sanitizePath($parts["dirname"]).FileLogWriterHelper::FOLDER_SEPARATOR.".htaccess";		
			$files_tag_open= '<Files "'.FileLogWriterHelper::sanitizeFilename($parts["basename"]).'">';
			if (!$overwrite) {
				$mode= "a";				
				if (file_exists($saveLocation)) {
					if (strpos(file_get_contents($saveLocation), $files_tag_open) !== false) $pass= false;
				}
			} else {
				$mode= "w";				
			}
			$eol= FileLogWriterHelper::getOsEol();
			$htaccess = $files_tag_open.$eol;
			$htaccess.= '	Order Allow, Deny'.$eol;
			$htaccess.= '	Deny from all'.$eol;
			$htaccess.= '</Files>'.$eol;
			if ($pass) {
				$ok= FileLogWriterHelper::writeToFile($saveLocation, $htaccess, $mode);
				if (!$ok) $ret= false;
			}
		}

		return $ret;
	}

	/** 
	  * Read datatype mappings ini file and return a parameter array.
	  *
	  * @param string $filename
	  * @return array
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
	static public function getDatatypeMappingsFromIni($filename= null) {		
		$ret= null;
		
		$filename= self::prepareIniPath($filename);
		if (!$filename || $filename=="") {					
			$filename= __DIR__.FileLogWriterHelper::FOLDER_SEPARATOR.DatabaseLogWriter::DATATYPE_MAPPINGS_DEFAULT_INI;
		}
		
		if (file_exists($filename)) $ret= parse_ini_file($filename, true);
		
		return $ret;
	}
	
	/** 
	  * Sanitize a given .ini path and filename.
	  *
	  * @param string $inifile
	  * @return string
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  */
	static protected function prepareIniPath($inifile) {
		$ret= null;
		
		if ($inifile) {
			$document_root= FileLogWriterHelper::pathHelperHarmonizeTrailingSeparator($_SERVER['DOCUMENT_ROOT']);
			$parts= pathinfo(FileLogWriterHelper::sanitizePath($inifile));			
			if (!FileLogWriterHelper::pathLeavesOrEqualsRoot($parts["dirname"], $document_root)) {
				$path= FileLogWriterHelper::sanitizePath($document_root.$parts["dirname"]);
				$ret= FileLogWriterHelper::FOLDER_SEPARATOR.$path.FileLogWriterHelper::sanitizeFilename($parts["basename"]);
			}
		}
		
		return $ret;
	}

}
?>