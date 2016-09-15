<?php
namespace phpWTL;
use phpWTL\DataRetrievalPolicy;
use phpWTL\DataRetrievalPolicyHelper;
use phpWTL\ExtendedDRP;
use phpWTL\aBasicLogger;
use phpWTL\ExtendedFormatDescriptor;
use phpWTL\LoggerContent;
use phpWTL\ExtendedDataRetriever;
use phpWTL\GenericDataValidator;
use phpWTL\ExtendedDataFormatter;

require_once 'DataRetrievalPolicy.php';
require_once 'DataRetrievalPolicyHelper.php';
require_once 'ExtendedDRP.php';
require_once 'aBasicLogger.php';
require_once 'ExtendedFormatDescriptor.php';
require_once 'LoggerContent.php';
require_once 'ExtendedDataRetriever.php';
require_once 'GenericDataValidator.php';
require_once 'ExtendedDataFormatter.php';

/**
  * Logger for the extended log file format (see: https://www.w3.org/TR/WD-logfile.html). 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.3.3
  * @api
  */
class ExtendedLogger extends aBasicLogger {
	protected static $time_taken_start= null;
	protected static $time_taken_stop= null;
		
	/**
	  * The constructor must perform the following taks:
	  *	  
	  * - Call "loadRetrievalPolicies" or handle the policies otherwise appropriately
	  * - Instantiate and store a LoggerContent object based on the required static FormatDescriptor
	  * - Handle/store all other (optional) parts neccessary and/or wanted (data retriever, validator, formatter)
	  * - Handle buffer initialization for ContentLengthRetrieval policy
	  * - Handle start timestamp measurement for time-taken field
	  *
	  * @param object $retrievalPolicies Provide policies for data retrieval (if applicable). 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0	  
	  */
	protected function __construct($retrievalPolicies= null) {
		static::setDataRetrievalPolicies($retrievalPolicies);
		static::$loggerContent= new LoggerContent(ExtendedFormatDescriptor::getInstance());
		static::$dataRetriever= ExtendedDataRetriever::getInstance(array(static::$loggerContent, static::$retrievalPolicies));
		static::$dataValidator= GenericDataValidator::getInstance(static::$loggerContent);
		static::$dataFormatter= ExtendedDataFormatter::getInstance(static::$loggerContent);
		if (DataRetrievalPolicyHelper::existsDataRetrievalPolicy(static::$retrievalPolicies, ExtendedDRP::DRP_EXT_CONTENT_LENGTH_RETRIEVAL) &&
			DataRetrievalPolicyHelper::getDataRetrievalPolicyFlag(static::$retrievalPolicies, ExtendedDRP::DRP_EXT_CONTENT_LENGTH_RETRIEVAL)==ExtendedDRP::DRP_EXT_CLR_BUFFER) {
			static::initializeBuffering();
		}
		static::takeTime();
	}
	
	/**
	  * Buffer initialization for ContentLengthRetrieval policy.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  */
	protected function initializeBuffering() {
		ob_end_clean();
		ob_start();
	}
	
	/**
	  * Buffer finalization for ContentLengthRetrieval policy (flush, store content-length).
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  */
	protected function finalizeBuffering() {
		$ob_size= ob_get_length();
		ob_end_flush();
		if (static::$dataRetriever) {
			DataRetrievalPolicyHelper::setDataRetrievalPolicyParameter(static::$retrievalPolicies, ExtendedDRP::DRP_EXT_CONTENT_LENGTH_RETRIEVAL, $ob_size);
			static::$dataRetriever->setDataRetrievalPolicies(static::$retrievalPolicies);
		}
	}

	/**
	  * Handle time measurement.
	  *
	  * @param boolean $stop Start or stop measurement (default= false= start).
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  */
	public function takeTime($stop= false) {
		if ($stop) {
			static::$time_taken_stop= static::microtimeMilis();
		} else {
			static::$time_taken_start= static::microtimeMilis();
		}
	}

	/**
	  * Handle time measurement.
	  *
	  * @return miliseconds.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  */
	public function timeTaken() {
		$ret= 0;
		
		if (static::$time_taken_start && static::$time_taken_stop) {
			$ret= (static::$time_taken_stop - static::$time_taken_start);
		}

		return $ret;
	}

	/**
	  * microtime float value.
	  *
	  * @return miliseconds.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  */
	public function microtimeMilis() {				
		list($usec, $sec)= explode(" ", microtime());
		return ($sec*1000) + round($usec*1000);
	}
	
	/**
	  * Set default data retrieval policies.
	  *	  
	  * @param object $retrievalPolicies Provide policies for data retrieval. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  */
	protected function loadDataRetrievalPoliciesDefault() {
		static::$retrievalPolicies= array(
			new DataRetrievalPolicy(array(
				'name' => ExtendedDRP::DRP_EXT_CONTENT_LENGTH_RETRIEVAL, 
				'flag' => ExtendedDRP::DRP_EXT_CLR_SCRIPT
			)),
		);
	}
	
	/**
	  * Set data retrieval policies after initialization:
	  *	  
	  * - Also do this in the associated data retriever 
	  * - If null restore the default
	  * - Handle buffer reset/initialization for ContentLengthRetrieval policy
	  *	  
	  * @param array $retrievalPolicies Provide policies for data retrieval. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function setDataRetrievalPolicies($retrievalPolicies= null) {		
		if ($retrievalPolicies) {
			static::$retrievalPolicies= $retrievalPolicies;
		} else {
			static::loadDataRetrievalPoliciesDefault();
		}
		if (static::$dataRetriever) static::$dataRetriever->setDataRetrievalPolicies(static::$retrievalPolicies);
		if (DataRetrievalPolicyHelper::existsDataRetrievalPolicy(static::$retrievalPolicies, ExtendedDRP::DRP_EXT_CONTENT_LENGTH_RETRIEVAL) &&
			DataRetrievalPolicyHelper::getDataRetrievalPolicyFlag(static::$retrievalPolicies, ExtendedDRP::DRP_EXT_CONTENT_LENGTH_RETRIEVAL)==ExtendedDRP::DRP_EXT_CLR_BUFFER) {
			static::initializeBuffering();
		}
	}
	
	/**
	  * Perform the actual logging process:
	  *	  
	  * - Handle buffer flush and buffer-size measurement for ContentLengthRetrieval policy
	  * - Handle stop timestamp measurement for time-taken field
	  * - Retrieve log data thru retriever
	  * - Validate data fields with validator (can be turned off)
	  * - Format/prefix/suffix fields via formatter (can be turned off), set the datatype class in content object accordingly
	  *
	  * @param array $params Logger parameters (bool "validate" default "false", bool "format" default "true")
	  * @return array Validation errors (null if none)
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.2.3
	  * @api
	  */
	public function log($params= null) {
		static::takeTime(true);
		$ret= null;
		
		$validate= false;
		$format= true;
		if ($params && is_array($params)) {
			if (array_key_exists("validate", $params)) {
				if ($params["validate"]) $validate= true; else $validate= false;
			}
			if (array_key_exists("format", $params)) {
				if ($params["format"]) $format= true; else $format= false;
			}
		}

		if (DataRetrievalPolicyHelper::existsDataRetrievalPolicy(static::$retrievalPolicies, ExtendedDRP::DRP_EXT_CONTENT_LENGTH_RETRIEVAL) &&
			DataRetrievalPolicyHelper::getDataRetrievalPolicyFlag(static::$retrievalPolicies, ExtendedDRP::DRP_EXT_CONTENT_LENGTH_RETRIEVAL)==ExtendedDRP::DRP_EXT_CLR_BUFFER) {
			static::finalizeBuffering();
		}
		if (static::$dataRetriever) {
			static::$dataRetriever->retrieve();
			static::$dataRetriever->setFieldContent("time-taken", static::timeTaken());
			static::setDirectives();
		}
		if ($validate && static::$dataValidator) $ret= static::$dataValidator->validate();
		if ($format && static::$dataFormatter) {
			static::$dataFormatter->formatAll();
			static::$loggerContent->setDatatypeClass(FormatDescriptorHelper::DATATYPE_FORMATTED);
		} else {
			static::$loggerContent->setDatatypeClass(FormatDescriptorHelper::DATATYPE_RAW);
		}
		
		return $ret;
	}

	/**
	  * Build directives (meta data) content lines for a file writer
	  *	  
	  * @param array $fields_whitelist If given two things will be done: a) only fields matching the list will be included in the "Fields" directive and b) they will be sorted according to the order of the whitelist.  
	  * @return array Lines
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.2
	  * @api
	  */
	public function buildDirectivesForFileWriter($fields_whitelist= null) {
		$ret= array();
		
		if (static::$loggerContent) {
			$meta= static::getDirectives($fields_whitelist);
			if ($meta && is_array($meta)) foreach ($meta as $k=>$f) {
				$ret[$k]= static::$loggerContent->getFormatDescriptor()->getCaption($k).": ".$f;
			}
		}
		
		return $ret;
	}
	
	/**
	  * Set directives (meta data) array
	  *	  
	  * @param array $fields_whitelist If given two things will be done: a) only fields matching the list will be included in the "Fields" directive and b) they will be sorted according to the order of the whitelist.  
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function setDirectives($fields_whitelist= null) {
		if (static::$loggerContent) {
			static::$loggerContent->__set("dir_fields", static::retrieveFieldsDirectiveContent($fields_whitelist));
		}
	}

	/**
	  * Get directives (meta data) array
	  *	  
	  * @param array $fields_whitelist If given two things will be done: a) only fields matching the list will be included in the "Fields" directive and b) they will be sorted according to the order of the whitelist.  
	  * @return array
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  */
	protected function getDirectives($fields_whitelist= null) {
		$ret= array();
		
		if (static::$loggerContent) {
			static::$loggerContent->__set("dir_fields", static::retrieveFieldsDirectiveContent($fields_whitelist));
			$meta= static::$loggerContent->toArrayMeta();
			if ($meta && is_array($meta)) {
				$ret= $meta;
			}
		}
		
		return $ret;
	}

	/**
	  * Return String for "Fields" Directive
	  *	  
	  * @param array $fields_whitelist If given two things will be done: a) only fields matching the list will be included in the "Fields" directive and b) they will be sorted according to the order of the whitelist.  
	  * @return string
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.2
	  */
	protected function retrieveFieldsDirectiveContent($fields_whitelist= null) {
		$value= "";
		
		if ($fields_whitelist && is_array($fields_whitelist)) {
			$default= FormatDescriptorHelper::DEFAULT_ANY;
		} else {
			$default= FormatDescriptorHelper::DEFAULT_ONLY;
		}
		$field_names= static::$loggerContent->getFormatDescriptor()->getRegularFieldNames($default);
		if ($fields_whitelist && is_array($fields_whitelist)) {
			// check if $fields_whitlist contains only valid field names, delete invalid names
			$valid_fields= static::$loggerContent->getFormatDescriptor()->getFieldNames();
			foreach ($fields_whitelist as $k=>$f) {
				if (!in_array($f, $valid_fields)) unset($fields_whitelist[$k]);
			}
			// only consider fields contained in $fields_whitelist
			$field_names= $fields_whitelist;
		}
		$content= static::$loggerContent->getFormatDescriptor()->getFieldData("caption", $field_names);
		// order according to $fields_whitelist
		if ($fields_whitelist && is_array($fields_whitelist)) {
			$content= static::$loggerContent->sortArray($content, $fields_whitelist);
		}
		$maxk= count($content)-1;
		foreach ($content as $k=>$f) {
			$value.= $f;
			if ($k != $maxk) $value.= " ";			
		}
		
		return $value;
	}

}
?>