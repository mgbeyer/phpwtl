<?php
namespace phpWTL;
use phpWTL\aBasicDataRetriever;
use phpWTL\LogWriter\FLW\FileLogWriterHelper;

require_once 'aBasicDataRetriever.php';
require_once 'LogWriter/FLW/FileLogWriterHelper.php';

/**
  * Data retriever for the extended log file format. 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.2.3
  * @api
  */
class ExtendedDataRetriever extends aBasicDataRetriever {
	
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
	  * @version v0.1.8
	  * @api
	  */
	public function retrieveField($field_name, $value= null) {
		if (static::$fieldDescriptor && static::$loggerContent) {			
			if ($value==null) {
				switch ($field_name) {
					case "date":
					case "time":
						$value= $_SERVER['REQUEST_TIME'];
					break;
					case "c-ip":
						$value= $_SERVER['REMOTE_ADDR'];
					break;
					case "cs-username":
						$value= (array_key_exists ('REMOTE_USER', $_SERVER) ? $_SERVER['REMOTE_USER'] : "-");
					break;
					case "s-computername":
						$value= $_SERVER['SERVER_NAME'];
					break;
					case "s-ip":
						$value= $_SERVER['SERVER_ADDR'];
					break;
					case "s-port":
						$value= $_SERVER['SERVER_PORT'];
					break;
					case "cs-method":
						$value= $_SERVER['REQUEST_METHOD'];
					break;
					case "cs-uri-stem":
					case "cs-uri-query":
						if (DataRetrievalPolicyHelper::existsDataRetrievalPolicy(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL) &&
						    DataRetrievalPolicyHelper::getDataRetrievalPolicyFlag(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL)==DRP::DRP_CLR_CUSTOM) {
							$requestTarget= DataRetrievalPolicyHelper::getDataRetrievalPolicyParameter(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL);
							$basepath= str_replace(basename($_SERVER["SCRIPT_FILENAME"]), "", $_SERVER['SCRIPT_FILENAME']);
							$basepath= str_replace($_SERVER['DOCUMENT_ROOT'], "", $basepath);
							$scriptBaseFull= str_replace(basename($_SERVER["SCRIPT_FILENAME"]), "", $_SERVER['SCRIPT_FILENAME']);
							$scriptBaseRel= str_replace($_SERVER['DOCUMENT_ROOT'], "", $scriptBaseFull);
							$rtParts= FileLogWriterHelper::separatePathAndFile($requestTarget);							
							if (FileLogWriterHelper::isAbsolutePath($requestTarget)) {
								// absolute target (with webserver root ("htdocs") as absolute root)								
								if (!FileLogWriterHelper::pathLeavesOrEqualsRoot($rtParts['pathname'], FileLogWriterHelper::FOLDER_SEPARATOR)) {
									$target= FileLogWriterHelper::sanitizePath($rtParts['pathname']);	
								} else {
									$target= $scriptBaseRel;
								}
							} else {
								// relative target								
								$target= FileLogWriterHelper::sanitizePath($scriptBaseRel.$rtParts['pathname']);
							}
							$value= FileLogWriterHelper::FOLDER_SEPARATOR.FileLogWriterHelper::cleanupPath($target).$rtParts['filename'];
						} else {
							$value= $_SERVER['REQUEST_URI'];
						}
						$uri= explode("?", $value);
						if (count($uri)<=1) {
							$stem= $value;
							$query= "-";
						} else {
							$stem= $uri[0];
							$query= $uri[1];
						}
						switch ($field_name) {
							case "cs-uri-stem":
								$value= $stem;
							break;
							case "cs-uri-query":
								$value= $query;
							break;
						}
					break;
					case "sc-status":
						$value= http_response_code();
					break;
					case "sc-bytes":
						$value= "";						
						if (DataRetrievalPolicyHelper::existsDataRetrievalPolicy(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL) &&
						    DataRetrievalPolicyHelper::getDataRetrievalPolicyFlag(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL)==DRP::DRP_CLR_CUSTOM) {
							$requestTarget= DataRetrievalPolicyHelper::getDataRetrievalPolicyParameter(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL);
							$basepath= str_replace(basename($_SERVER["SCRIPT_FILENAME"]), "", $_SERVER['SCRIPT_FILENAME']);
							if (FileLogWriterHelper::isAbsolutePath($requestTarget)) {
								$scriptBaseRel= str_replace($_SERVER['DOCUMENT_ROOT'], "", $basepath);
								$basepath= str_replace($scriptBaseRel, "", $basepath);
							}
							$requestpath= $basepath.$requestTarget;
							if (file_exists($requestpath)) {
								$value= filesize($requestpath);	
							} else {
								static::$loggerContent->__set("sc-status", "404");
							}						
						} elseif (DataRetrievalPolicyHelper::existsDataRetrievalPolicy(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL) &&
								  DataRetrievalPolicyHelper::getDataRetrievalPolicyFlag(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL)==DRP::DRP_CLR_BUFFER) {
							$value= DataRetrievalPolicyHelper::getDataRetrievalPolicyParameter(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL);
						} else {				
							$value= filesize($_SERVER['SCRIPT_FILENAME']);
						}
					break;
					case "time-taken":
						$value= "";	// must be set by logger in retrospect
					break;
					case "cs-version":
						$value= $_SERVER['SERVER_PROTOCOL'];
					break;
					case "cs-host":
						$value= (array_key_exists ('HTTP_HOST', $_SERVER) ? $_SERVER['HTTP_HOST'] : "-");
					break;
					case "cs-user-agent":
						$value= $_SERVER['HTTP_USER_AGENT'];
					break;
					case "cs-cookie":
						$value= (array_key_exists ('HTTP_COOKIE', $_SERVER) ? $_SERVER['HTTP_COOKIE'] : "-");
					break;
					case "cs-referrer":
						if (DataRetrievalPolicyHelper::existsDataRetrievalPolicy(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL) &&
						    DataRetrievalPolicyHelper::getDataRetrievalPolicyFlag(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL)==DRP::DRP_CLR_CUSTOM) {
							$referrer= static::getUrlOrigin().$_SERVER['REQUEST_URI'];
						} else {
							$referrer= isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : (array_key_exists("referrer", apache_request_headers()) ? apache_request_headers()["referrer"] : "-");
						}
						$value= $referrer;
					break;
					case "sc-substatus":
						$value= "-";
					break;

					// meta data (directives)
					case "dir_version":
						$value= static::$fieldDescriptor->getFormatVersion();
					break;
					case "dir_fields":
						$value= "-";	// must be set by logger (and prior to data storage) in retrospect, field order may be subject to change after retrieval by user
					break;
					case "dir_start-date":
						$value= time();
					break;
					case "dir_software":
						$value= "phpWhatTheLog, v".phpWTL::VERSION;
						$value.= " (" . (array_key_exists ('SERVER_SOFTWARE', $_SERVER) ? $_SERVER['SERVER_SOFTWARE'] : "-") . ")";
					break;
					case "dir_remark":
						$value= "-";
					break;
				}
				if ($value!="") static::$loggerContent->__set($field_name, $value);
			} else static::$loggerContent->__set($field_name, $value);
		}
	}
		
}
?>
