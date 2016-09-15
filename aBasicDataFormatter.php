<?php
namespace phpWTL;
use phpWTL\aSingleton;

require_once 'aSingleton.php';

/**
  * Abstract class for a content (logger field) formatter (also handling field enclosing marks). 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.2.0
  * @api
  */
abstract class aBasicDataFormatter extends aSingleton {
	protected static $loggerContent= null;
	protected static $fieldDescriptor= null;

	
	/**
	  * Format only a single log field and store it in the associated LoggerContent object.
	  *
	  * @param string $field_name ID of log format field. 
	  * @param string $value Provide an (optional) value to format and pass thru to the LoggerContent object, so allowing for the injection of external data.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	abstract public function formatField($field_name, $value= null);

	/**
	  * Format and enclose (prefix/suffix) all log fields.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function formatAll() {
		if (static::$fieldDescriptor && static::$loggerContent) {
			static::format();
			static::enclose();
		}		
	}

	/**
	  * Format only all log fields.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function format() {
		if (static::$fieldDescriptor && static::$loggerContent) {
			foreach (static::$fieldDescriptor->getFieldNames() as $k=>$f) {
				static::formatField($f);
			}
		}		
	}
	
	/**
	  * Format and enclose (prefix/suffix) a single log field and store it in the associated LoggerContent object.
	  *
	  * @param string $field_name ID of log format field. 
	  * @param string $value Provide an (optional) value to format and pass thru to the LoggerContent object, so allowing for the injection of external data.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function formatAllField($field_name, $value= null) {
		if (static::$fieldDescriptor && static::$loggerContent) {
			static::formatField($field_name, $value);
			static::encloseField($field_name, $value);
		}
	}
		
	/**
	  * Enclose only (prefix/suffix) all log fields.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function enclose() {
		if (static::$fieldDescriptor && static::$loggerContent) {
			foreach (static::$fieldDescriptor->getFieldNames() as $k=>$f) {
				static::encloseField($f);
			}
		}		
	}
	
	/**
	  * Enclose (prefix/suffix) a single log field and store it in the associated LoggerContent object.
	  *
	  * @param string $field_name ID of log format field. 
	  * @param string $value Provide an (optional) value to format and pass thru to the LoggerContent object, so allowing for the injection of external data.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function encloseField($field_name, $value= null) {
		if (static::$fieldDescriptor && static::$loggerContent) {
			$prefix= static::$fieldDescriptor->getPrefix($field_name);
			$suffix= static::$fieldDescriptor->getSuffix($field_name);
			if (!$value) $value= static::$loggerContent->__get($field_name);
			static::$loggerContent->__set($field_name, $prefix.$value.$suffix);
		}
	}
	
	
	/**
	  * Helper method to build content for the "request line" of the "common" and "combined" log format.
	  *
	  * @param string $request_method
	  * @param string $request_uri
	  * @param string $query_string May be blank
	  * @param string $protocol
	  * @return string Proper request line, format: request_method request_uri[?query_string] protocol
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function buildRequestLineString($request_method, $request_uri, $query_string = null, $protocol) {
		$uri= $query_string ? rtrim($request_uri, "?".$query_string) : $request_uri;
		return $request_method." ".$uri.($query_string ? "?".$query_string : "")." ".$protocol;
	}
	
	/**
	  * Helper method to split the content of a "request line" of the "common" and "combined" log format into its chunks.
	  *
	  * @param string $request_line
	  * @return array An associative array representing the single portions of the request line: request_method[0], request_uri[1], query_string[2], protocol[3].
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function explodeRequestLineString($request_line) {
		$ret= null;
		
		$prefix= $suffix= "";
		foreach (static::$formatFields as $k=>$f) {
			if ($f->name=="request_line") {
				$prefix= $f->prefix;
				$suffix= $f->suffix;
			}
		}
		$request_line= ltrim($request_line, $prefix);
		$request_line= rtrim($request_line, $suffix);
		$main= explode(" ", $request_line);
		$uri= explode("?", $main[1]);
		if (count($uri)<=1) {
			$ret= array(
				'request_method' => $main[0],
				'request_uri' => $main[1],
				'query_string' => "",
				'protocol' => $main[2]
			);
		} else {
			$ret= array(
				'request_method' => $main[0],
				'request_uri' => $uri[0],
				'query_string' => $uri[1],
				'protocol' => $main[2]
			);
		}		
		
		return $ret;
	}

}
?>
