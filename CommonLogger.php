<?php
namespace phpWTL;
use phpWTL\DataRetrievalPolicy;
use phpWTL\DataRetrievalPolicyHelper;
use phpWTL\DRP;
use phpWTL\aBasicLogger;
use phpWTL\CommonFormatDescriptor;
use phpWTL\LoggerContent;
use phpWTL\CommonDataRetriever;
use phpWTL\GenericDataValidator;
use phpWTL\CommonDataFormatter;

require_once 'DataRetrievalPolicy.php';
require_once 'DataRetrievalPolicyHelper.php';
require_once 'DRP.php';
require_once 'aBasicLogger.php';
require_once 'CommonFormatDescriptor.php';
require_once 'LoggerContent.php';
require_once 'CommonDataRetriever.php';
require_once 'GenericDataValidator.php';
require_once 'CommonDataFormatter.php';

/**
  * Logger for the NCSA common log format (see: https://en.wikipedia.org/wiki/Common_Log_Format). 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.3.3
  * @api
  */
class CommonLogger extends aBasicLogger {
	
	/**
	  * The constructor must perform the following taks:
	  *	  
	  * - Call "loadRetrievalPolicies" or handle the policies otherwise appropriately
	  * - Instantiate and store a LoggerContent object based on the required static FormatDescriptor
	  * - Handle/store all other (optional) parts neccessary and/or wanted (data retriever, validator, formatter)
	  * - Handle buffer initialization for ContentLengthRetrieval policy
	  *
	  * @param object $retrievalPolicies Provide policies for data retrieval (if applicable). 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0	  
	  */
	protected function __construct($retrievalPolicies= null) {
		static::setDataRetrievalPolicies($retrievalPolicies);
		static::$loggerContent= new LoggerContent(CommonFormatDescriptor::getInstance());
		static::$dataRetriever= CommonDataRetriever::getInstance(array(static::$loggerContent, static::$retrievalPolicies));
		static::$dataValidator= GenericDataValidator::getInstance(static::$loggerContent);
		static::$dataFormatter= CommonDataFormatter::getInstance(static::$loggerContent);
		if (DataRetrievalPolicyHelper::existsDataRetrievalPolicy(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL) &&
			DataRetrievalPolicyHelper::getDataRetrievalPolicyFlag(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL)==DRP::DRP_CLR_BUFFER) {
			static::initializeBuffering();
		}
	}
	
	/**
	  * Buffer initialization for ContentLengthRetrieval policy.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  */
	protected function initializeBuffering() {
		if (count(ob_get_status())>0) ob_end_clean();
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
			DataRetrievalPolicyHelper::setDataRetrievalPolicyParameter(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL, $ob_size);
			static::$dataRetriever->setDataRetrievalPolicies(static::$retrievalPolicies);
		}
	}

	/**
	  * Return PHP output buffer.
	  *
	  * @return string buffer content (null if logging mode is not buffered)
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getBuffer() {
		$ret= null;

		if (DataRetrievalPolicyHelper::existsDataRetrievalPolicy(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL) &&
			DataRetrievalPolicyHelper::getDataRetrievalPolicyFlag(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL)==DRP::DRP_CLR_BUFFER) {
			$ret= ob_get_contents();
		}		
		return $ret;
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
				'name' => DRP::DRP_CONTENT_LENGTH_RETRIEVAL, 
				'flag' => DRP::DRP_CLR_SCRIPT
			)),
		);
	}
	
	public function setDataRetrievalPolicies($retrievalPolicies= null) {		
		if ($retrievalPolicies) {
			static::$retrievalPolicies= $retrievalPolicies;
		} else {
			static::loadDataRetrievalPoliciesDefault();
		}
		if (static::$dataRetriever) static::$dataRetriever->setDataRetrievalPolicies(static::$retrievalPolicies);
		if (DataRetrievalPolicyHelper::existsDataRetrievalPolicy(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL) &&
			DataRetrievalPolicyHelper::getDataRetrievalPolicyFlag(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL)==DRP::DRP_CLR_BUFFER) {
			static::initializeBuffering();
		}
	}

	/**
	  * Perform the actual logging process:
	  *	  
	  * - Handle buffer flush and buffer-size measurement for ContentLengthRetrieval policy
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
		
		if (DataRetrievalPolicyHelper::existsDataRetrievalPolicy(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL) &&
			DataRetrievalPolicyHelper::getDataRetrievalPolicyFlag(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL)==DRP::DRP_CLR_BUFFER) {
			static::finalizeBuffering();
		}
		if (static::$dataRetriever) static::$dataRetriever->retrieve();
		if ($validate && static::$dataValidator) $ret= static::$dataValidator->validate();
		if ($format && static::$dataFormatter) {
			static::$dataFormatter->formatAll();
			static::$loggerContent->setDatatypeClass(FormatDescriptorHelper::DATATYPE_FORMATTED);
		} else {
			static::$loggerContent->setDatatypeClass(FormatDescriptorHelper::DATATYPE_RAW);
		}
		
		return $ret;
	}

}
?>