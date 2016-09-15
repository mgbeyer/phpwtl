<?php
namespace phpWTL;
use phpWTL\aBasicDataRetriever;

require_once 'aBasicDataRetriever.php';

/**
  * Data retriever for PHP event/error/exception logging. 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.0
  * @api
  */
class PhpAppDataRetriever extends aBasicDataRetriever {

	
	/**
	  * @param object $loggerContent Provide a LoggerContent object. The FieldDescriptor is derived from the LoggerContent object.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  */
	protected function __construct($loggerContent= null) {
		static::$loggerContent= $loggerContent;
		if (static::$loggerContent) static::$fieldDescriptor= $loggerContent->getFormatDescriptor();	
	}

	
	/**
	  * Retrieve data for a single log field and store it in the associated LoggerContent object.
	  *
	  * @param string $field_name ID of log format field. 
	  * @param string $value Provide an (optional) value to pass thru to the LoggerContent object, so allowing for the injection of external data.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function retrieveField($field_name, $value= null) {
		if (static::$fieldDescriptor && static::$loggerContent) {
			if ($value==null) {
				switch ($field_name) {
					case "timestamp":
						$value= time();
					break;
				}
				if ($value!="") static::$loggerContent->__set($field_name, $value);
			} else static::$loggerContent->__set($field_name, $value);
		}
	}
		
}
?>
