<?php
namespace phpWTL\Tools;
use phpWTL\LogWriter\FLW\FileLogWriterHelper;

require_once __DIR__ .'/../LogWriter/FLW/FileLogWriterHelper.php';

/**
  * Helper class for P.E.P.R.
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.1
  * @api All constant names (actual values might be subject to change)
  */
class PEPRHelper {

	/** 
	  * Read tag resource mappings ini file and return a parameter array.
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
			$filename= __DIR__.FileLogWriterHelper::FOLDER_SEPARATOR.PEPR::TAG_RESOURCE_MAPPINGS_DEFAULT_INI;
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
	  * @version v0.1.2
	  */
	static protected function prepareIniPath($inifile) {
		$ret= null;
		
		if ($inifile) {
			$document_root= FileLogWriterHelper::pathHelperHarmonizeTrailingSeparator($_SERVER['DOCUMENT_ROOT']);
			$parts= pathinfo(FileLogWriterHelper::sanitizePath($inifile));			
			if (!FileLogWriterHelper::pathLeavesOrEqualsRoot($parts["dirname"], $document_root)) {
				$path= FileLogWriterHelper::sanitizePath($document_root.$parts["dirname"]);
				$ret= $path.FileLogWriterHelper::sanitizeFilename($parts["basename"]);
			}
		}
		
		return $ret;
	}

}
?>