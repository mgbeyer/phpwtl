<?php
namespace phpWTL;
use phpWTL\PhpAppLoggerHelper;
use phpWTL\aBasicLogger;
use phpWTL\PhpAppFormatDescriptor;
use phpWTL\LoggerContent;
use phpWTL\PhpAppDataRetriever;
use phpWTL\GenericDataValidator;
use phpWTL\PhpAppDataFormatter;

require_once 'PhpAppLoggerHelper.php';
require_once 'aBasicLogger.php';
require_once 'PhpAppFormatDescriptor.php';
require_once 'LoggerContent.php';
require_once 'PhpAppDataRetriever.php';
require_once 'GenericDataValidator.php';
require_once 'PhpAppDataFormatter.php';

/**
  * Logger for PHP event/error/exception logging. 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.1
  * @api
  */
class PhpAppLogger extends aBasicLogger {
	protected static $logLevelThreshold= null;
	private static $loggerContent_bak= null;
	
	/**
	  * The constructor must perform the following taks:
	  *	  
	  * - Instantiate and store a LoggerContent object based on the required static FormatDescriptor
	  * - Handle/store all other (optional) parts neccessary and/or wanted (data retriever, validator, formatter)
	  *
	  * @param int $loglevel Threshold for logger (default: LOGLEVEL_WARNING). 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1	  
	  */
	protected function __construct($loglevel= null) {
		static::setLoglevelThreshold($loglevel);
		static::$loggerContent= new LoggerContent(PhpAppFormatDescriptor::getInstance());
		static::$dataRetriever= PhpAppDataRetriever::getInstance(static::$loggerContent);
		static::$dataValidator= GenericDataValidator::getInstance(static::$loggerContent);
		static::$dataFormatter= PhpAppDataFormatter::getInstance(static::$loggerContent);;
	}
	
	/**
	  * Interpolate context values into the (brace-delimited) message placeholders (see also: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md).
	  *
	  * @param string $message
	  * @param array $context Key-value pairs for context data
	  * @return string Message with all {placeholders} replaced by their respective context variable contents.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function interpolateContextData($message, array $context = array()) {
		// build a replacement array with braces around the context keys
		$replace = array();
		foreach ($context as $key => $val) {
			// check that the value can be casted to string
			if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
				$replace['{' . $key . '}'] = $val;
			}
		}

		// interpolate replacement values into the message and return
		return strtr($message, $replace);
	}

	/**
	  * Transform contents of the given exception object into an associative array.
	  *
	  * @param exception $e
	  * @return array Array representation of exception object, keys analog to the corr. methods of the exception object: eMessage, ePrevious, eCode, eFile, eLine, eTrace, eTraceAsString, eToString (__toString of whole ex. obj.).
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function e2arr($e) {
		$ret= array();
		
		if (is_a($e, "Exception")) {
			$ret["eMessage"]= $e->getMessage();
			$ret["ePrevious"]= $e->getPrevious();
			$ret["eCode"]= $e->getCode();
			$ret["eFile"]= $e->getFile();
			$ret["eLine"]= $e->getLine();
			$ret["eTrace"]= $e->getTrace();
			$ret["eTraceAsString"]= $e->getTraceAsString();
			$ret["eToString"]= $e->__toString();
		}
				
		return $ret;
	}
	/**
	  * Transform contents of the given exception object into a JSON data structure (wrapper).
	  *
	  * @param exception $e
	  * @param int $jsonParams Parameter for "json_encode" function (optional)
	  * @return string JSON representation of exception object, keys analog to the corr. methods of the exception object: eMessage, ePrevious, eCode, eFile, eLine, eTrace, eTraceAsString, eToString (__toString of whole ex. obj.).
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
	public function e2json($e, $jsonParams= null) {
		return json_encode(static::e2arr($e), $jsonParams);
	}
	
	/**
	  * Extract placeholder names from message string.
	  *
	  * @param string $message
	  * @return array Placeholder names (keys).
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  */
	protected function extractPlaceholders($message) {
		$ret= array();
		
		$exclude_keys= array();
		preg_match_all("/{(\S+)}/", $message, $exclude_keys);
		if ($exclude_keys) $ret= $exclude_keys[1];
		
		return $ret;
	}

	/**
	  * Transform context array into a JSON data structure.
	  *
	  * @param array $context
	  * @param array $exclude_keys
	  * @param int $jsonParams Parameter for "json_encode" function (optional)
	  * @return string JSON representation of context data.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.2.2
	  */
	protected function context2json($context, $exclude_keys, $jsonParams= null) {
		$ret= array();
		
		if (!$exclude_keys || !is_array($exclude_keys)) $exclude_keys= array();
		foreach ($context as $key => $val) {
			if (!in_array($key, $exclude_keys)) {
				if (is_array($val)) {
					$ret[$key]= json_decode(static::context2json($val, $exclude_keys, $jsonParams), true);
				}
				if (strtolower($key)=="exception" && is_a($val, "Exception")) {
					$ret["exception"]= static::e2arr($val);
				} else {
					if (is_null($val)) {
						$ret[$key]= "";
					}
					if (method_exists($val, '__toString')) {
						$ret[$key]= $val->__toString();	
					} else {
						if (is_scalar($val) || is_string($val)) {
							$ret[$key]= $val;
						}
					}		
				}
			}
		}
		
		return json_encode($ret, $jsonParams);
	}

	/**
	  * Return loglevel threshold.
	  *	  
	  * @return int
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getLoglevelThreshold() {
		return static::$logLevelThreshold;
	}
	/**
	  * Set loglevel threshold.
	  *	  
	  * @param int $loglevel. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function setLoglevelThreshold($loglevel= null) {		
		if ($loglevel) {
			static::$logLevelThreshold= $loglevel;
		} else {
			static::$logLevelThreshold= PhpAppLoggerHelper::LOGLEVEL_WARNING;
		}		
	}

	/**
	  * Perform the actual logging process:
	  *	  
	  * - Check loglevel threshold
	  * - Retrieve log data thru retriever
	  * - Validate data fields with validator (can be turned off)
	  * - Format/prefix/suffix fields via formatter (can be turned off), set the datatype class in content object accordingly
	  *
	  * @param array $params Logger parameters (int "loglevel", string "message", array "context", bool "validate" default "false", bool "format" default "true")
	  * @return array Validation (or other) errors (null if none)
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.3
	  * @api
	  */
	public function log($params= null) {
		$ret= null;
		
		$validate= false;
		$format= true;
		$exclude_placeholders_from_context= true;
		$loglevel= null;
		$message= null;
		$context= array();
		if ($params && is_array($params)) {
			if (array_key_exists("validate", $params)) {
				if ($params["validate"]) $validate= true; else $validate= false;
			}
			if (array_key_exists("format", $params)) {
				if ($params["format"]) $format= true; else $format= false;
			}
			if (array_key_exists("exclude_placeholders_from_context", $params)) {
				if ($params["exclude_placeholders_from_context"]) $exclude_placeholders_from_context= true; else $exclude_placeholders_from_context= false;
			}
			if (array_key_exists("loglevel", $params)) {
				$loglevel= $params["loglevel"];
			}
			if (array_key_exists("message", $params)) {
				$message= $params["message"];
			}
			if (array_key_exists("context", $params)) {
				if (is_array($params["context"])) $context= $params["context"];
			}
		}
		
		if ($loglevel<=static::$logLevelThreshold) {
			if (static::$loggerContent_bak) static::$loggerContent= static::$loggerContent_bak;
			if (static::$dataRetriever) {
				static::$dataRetriever->retrieve();
				static::$dataRetriever->setFieldContent("message", static::interpolateContextData($message, $context));
				static::$dataRetriever->setFieldContent("loglevel", $loglevel);
				static::$dataRetriever->setFieldContent("loglevel_caption", PhpAppLoggerHelper::$LOGLEVEL_CAPTION[$loglevel]);
				$exclude_keys= array();
				if ($exclude_placeholders_from_context) $exclude_keys= static::extractPlaceholders($message);
				static::$dataRetriever->setFieldContent("context_data", static::context2json($context, $exclude_keys));
			}
			if ($validate && static::$dataValidator) $ret= static::$dataValidator->validate();
			if ($format && static::$dataFormatter) {
				static::$dataFormatter->formatAll();
				static::$loggerContent->setDatatypeClass(FormatDescriptorHelper::DATATYPE_FORMATTED);
			} else {
				static::$loggerContent->setDatatypeClass(FormatDescriptorHelper::DATATYPE_RAW);
			}
		} else {
			static::$loggerContent= null;
			$ret= array("loglevel above threshold.");
		}
				
		return $ret;
	}

}
?>