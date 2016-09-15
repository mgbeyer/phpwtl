<?php
namespace phpWTL;
use phpWTL\aSingleton;

require_once 'aSingleton.php';

/**
  * Abstract class for a content (logger field) validator. 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.2.0
  * @api
  */
abstract class aBasicDataValidator extends aSingleton {
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
	  * Validate all log fields.
	  *
	  * @return array Validation errors (contains field names which did not validate), null if none
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.2
	  * @api
	  */
	public function validate() {
		$err= null;
		if (static::$fieldDescriptor && static::$loggerContent) {
			foreach (static::$fieldDescriptor->getFieldNames() as $k=>$f) {
				if (!static::isValid($f)) array_push($err, $f);
			}
		}		
		return $err;
	}
	
	/**
	  * Validate data for a single log field.
	  *
	  * @param string $field_name ID of log format field. 
	  * @param string $value Provide an (optional) value to validate, so allowing for the validation of external data (by default validation will be performed on the LoggerContent object).
	  * @return boolean Field valid?.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function isValid($field_name, $value= null) {
		$ret= true;
		if (static::$fieldDescriptor && static::$loggerContent) {
			$validator= static::$fieldDescriptor->getValidator($field_name);
			if (!$value) $value= static::$loggerContent->__get($field_name);
			if ($validator) $ret= preg_match($validator, $value);
		}
		return $ret;
	}
		
}
?>
