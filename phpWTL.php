<?php
namespace phpWTL;

/**
  * General global includes
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.4
  * @api All constant names (actual values might be subject to change)
  */
class phpWTL {
	/** Overall phpWTL version/revision. */
	const VERSION= "0.3.3-alpha";
	/** Default character encoding. */
	const DEFAULT_ENCODING= "UTF-8";
	/** Default character encoding detection order. */
	const DEFAULT_ENCODING_DETECTION_ORDER= array("UTF-8", "Windows-1252", "ISO-8859-1");
	/** Flag for PHP default character encoding. */
	const SYSTEM_ENCODING= "_e_sys_";
	/** Assume system/PHP default character encoding. */
	const ENCODING_ASSUMPTION_SYSTEM= "_ea_sys_";
	/** Probe data for character encoding. */
	const ENCODING_ASSUMPTION_PROBE_DATA= "_ea_dat_";
	/** Folder name of Doctrine 2 DBAL installation. */
	const DBAL_FOLDER_NAME= "dbal-2.5.4";

	/**
	  * @return string PHP internal default encoding. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
    public static function getPhpDefaultEncoding() {
		$ret= null;
		
		if (ini_get("default_charset")) {
			// since PHP v5.6.0 this is the method of choice!
			$ret= ini_get("default_charset");
		} else {
			if (extension_loaded("mbstring")) {
				// for older PHP inst. this might do
				if (mb_internal_encoding()) $ret= mb_internal_encoding();
			} else {
				if (extension_loaded("iconv")) {
					// maybe last resort for old PHP inst., deprecated as of v5.6.0
					if (iconv_get_encoding("internal_encoding")) $ret= iconv_get_encoding("internal_encoding");
				}	
			}
		}
		
		return $ret;
	}

}
?>