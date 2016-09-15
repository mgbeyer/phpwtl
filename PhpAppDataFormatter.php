<?php
namespace phpWTL;
use phpWTL\aBasicDataFormatter;

require_once 'aBasicDataFormatter.php';

/**
  * Data formatter for PHP event/error/exception logging. 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.0
  * @api
  */
class PhpAppDataFormatter extends aBasicDataFormatter {
	protected static $loggerContent= null;
	protected static $fieldDescriptor= null;

	
	/**
	  * @param object $loggerContent Provide a LoggerContent object. Also store the format blueprint via the LoggerContent object (which knows its FormatDescriptor).
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  */
	protected function __construct($loggerContent= null) {
		static::$loggerContent= $loggerContent;
		if (static::$loggerContent) static::$fieldDescriptor= $loggerContent->getFormatDescriptor();	
	}

	
	/**
	  * Format only a single log field and store it in the associated LoggerContent object.
	  *
	  * @param string $field_name ID of log format field. 
	  * @param string $value Provide an (optional) value to format and pass thru to the LoggerContent object, so allowing for the injection of external data.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
	public function formatField($field_name, $value= null) {		
		if (static::$fieldDescriptor && static::$loggerContent) {
			$format_string= static::$fieldDescriptor->getFormatter($field_name);
			if (!$value) $value= static::$loggerContent->__get($field_name);
			switch ($field_name) {
				case "timestamp":
					// zone info from strftime is not really reliable in terms of format (at least under Win)
					// so if it is wanted let's get rid of it and build our own ;)
					$o= date('Z')/3600;
					$o= sprintf("%+03d", $o);
					$zone_offset= str_pad($o, 5, "0", STR_PAD_RIGHT);
					$format_string= preg_replace("/%z|%Z/", $zone_offset, $format_string);
					$value= strftime($format_string, $value);
				break;
				case "message":
				break;
				case "loglevel_caption":
				break;
				case "loglevel":
				break;
				case "context_data":
				break;
			}
			static::$loggerContent->__set($field_name, $value);
		}
	}

}
?>
