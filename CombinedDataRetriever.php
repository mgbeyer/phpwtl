<?php
namespace phpWTL;
use phpWTL\CommonDataRetriever;

require_once 'CommonDataRetriever.php';

/**
  * Data retriever for the combined log format. 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.2.1
  * @api
  */
class CombinedDataRetriever extends CommonDataRetriever {

	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.2
	  * @api
	  */
	public function retrieveField($field_name, $value= null) {
		parent::retrieveField($field_name, $value);
		
		if (static::$fieldDescriptor && static::$loggerContent) {
			if ($value==null) {
				switch ($field_name) {
					case "referrer":
						if (DataRetrievalPolicyHelper::existsDataRetrievalPolicy(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL) &&
						    DataRetrievalPolicyHelper::getDataRetrievalPolicyFlag(static::$retrievalPolicies, DRP::DRP_CONTENT_LENGTH_RETRIEVAL)==DRP::DRP_CLR_CUSTOM) {
							$referrer= static::getUrlOrigin().$_SERVER['REQUEST_URI'];
						} else {
							$referrer= isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : (array_key_exists("referrer", apache_request_headers()) ? apache_request_headers()["referrer"] : "");
						}
						$value= $referrer;
					break;
					case "user_agent":
						$value= $_SERVER['HTTP_USER_AGENT'];
					break;
				}
				if ($value!="") static::$loggerContent->__set($field_name, $value);
			} else static::$loggerContent->__set($field_name, $value);			
		}
	}
		
}
?>
