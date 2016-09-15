<?php
namespace phpWTL;
use phpWTL\aBasicDataRetriever;

require_once 'aBasicDataRetriever.php';

/**
  * Data retriever for the common log format. 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.2.1
  * @api
  */
class CommonDataRetriever extends aBasicDataRetriever {

	
	/**
	  * @param array $inject Array containing objects for LoggerContent [0] and RetrievalPolicies [1] (may be null). The FieldDescriptor is derived from the LoggerContent object.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  */
	protected function __construct($inject= null) {
		if ($inject && count($inject)==2) {
			static::$loggerContent= $inject[0];
			if ($inject[1]) static::$retrievalPolicies= $inject[1];
			static::$fieldDescriptor= static::$loggerContent->getFormatDescriptor();
		}
	}

	
	/**
	  * Retrieve data for a single log field and store it in the associated LoggerContent object.
	  *
	  * @param string $field_name ID of log format field. 
	  * @param string $value Provide an (optional) value to pass thru to the LoggerContent object, so allowing for the injection of external data.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.3
	  * @api
	  */
	public function retrieveField($field_name, $value= null) {
		if (static::$fieldDescriptor && static::$loggerContent) {
			if ($value==null) {
				switch ($field_name) {
					case "host_ip":
						$value= $_SERVER['REMOTE_ADDR'];
					break;
					case "client_identity":
						$value= "-";
					break;
					case "user_id":
						$value= (array_key_exists ('REMOTE_USER', $_SERVER) ? $_SERVER['REMOTE_USER'] : "-");
					break;
					case "timestamp":
						$value= FormatDescriptorHelper::timestamp2datetimeString($_SERVER['REQUEST_TIME']);
					break;
					case "request_line":
						if (DataRetrievalPolicyHelper::existsDataRetrievalPolicy(static::$retrievalPolicies, CommonCombinedDRP::DRP_CC_CONTENT_LENGTH_RETRIEVAL) &&
						    DataRetrievalPolicyHelper::getDataRetrievalPolicyFlag(static::$retrievalPolicies, CommonCombinedDRP::DRP_CC_CONTENT_LENGTH_RETRIEVAL)==CommonCombinedDRP::DRP_CC_CLR_CUSTOM) {
							$requestTarget= DataRetrievalPolicyHelper::getDataRetrievalPolicyParameter(static::$retrievalPolicies, CommonCombinedDRP::DRP_CC_CONTENT_LENGTH_RETRIEVAL);
							$basepath= rtrim($_SERVER['SCRIPT_FILENAME'], basename($_SERVER["SCRIPT_FILENAME"]));
							$basepath= "/".ltrim($basepath, $_SERVER['DOCUMENT_ROOT']);
							$value= $_SERVER['REQUEST_METHOD']." ".$basepath.$requestTarget." ".$_SERVER['SERVER_PROTOCOL'];
						} else {
							$value= $_SERVER['REQUEST_METHOD']." ".$_SERVER['REQUEST_URI']." ".$_SERVER['SERVER_PROTOCOL'];
						}
					break;
					case "status_code":
						$value= http_response_code();
					break;
					case "content_size":
						$value= "0";						
						if (DataRetrievalPolicyHelper::existsDataRetrievalPolicy(static::$retrievalPolicies, CommonCombinedDRP::DRP_CC_CONTENT_LENGTH_RETRIEVAL) &&
						    DataRetrievalPolicyHelper::getDataRetrievalPolicyFlag(static::$retrievalPolicies, CommonCombinedDRP::DRP_CC_CONTENT_LENGTH_RETRIEVAL)==CommonCombinedDRP::DRP_CC_CLR_CUSTOM) {
							$requestTarget= DataRetrievalPolicyHelper::getDataRetrievalPolicyParameter(static::$retrievalPolicies, CommonCombinedDRP::DRP_CC_CONTENT_LENGTH_RETRIEVAL);
							$basepath= rtrim($_SERVER['SCRIPT_FILENAME'], basename($_SERVER["SCRIPT_FILENAME"]));
							$requestpath= $basepath.$requestTarget;
							if (file_exists($requestpath)) {
								$value= filesize($requestpath);	
							} else {
								static::$loggerContent->__set("status_code", "404");
							}						
						} elseif (DataRetrievalPolicyHelper::existsDataRetrievalPolicy(static::$retrievalPolicies, CommonCombinedDRP::DRP_CC_CONTENT_LENGTH_RETRIEVAL) &&
								  DataRetrievalPolicyHelper::getDataRetrievalPolicyFlag(static::$retrievalPolicies, CommonCombinedDRP::DRP_CC_CONTENT_LENGTH_RETRIEVAL)==CommonCombinedDRP::DRP_CC_CLR_BUFFER) {
							$value= DataRetrievalPolicyHelper::getDataRetrievalPolicyParameter(static::$retrievalPolicies, CommonCombinedDRP::DRP_CC_CONTENT_LENGTH_RETRIEVAL);
						} else {				
							$value= filesize($_SERVER['SCRIPT_FILENAME']);
						}
					break;
				}
				if ($value!="") static::$loggerContent->__set($field_name, $value);
			} else static::$loggerContent->__set($field_name, $value);
		}
	}
		
}
?>
