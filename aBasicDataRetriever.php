<?php
namespace phpWTL;
use phpWTL\aSingleton;

require_once 'aSingleton.php';

/**
  * Abstract data retriever class. 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.2.0
  * @api
  */
abstract class aBasicDataRetriever extends aSingleton {
	protected static $loggerContent= null;
	protected static $fieldDescriptor= null;
	protected static $retrievalPolicies= null;

	
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
	abstract public function retrieveField($field_name, $value= null);

	/**
	  * Retrieve data for all log fields and store it in the LoggerContent object.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function retrieve() {
		if (static::$fieldDescriptor && static::$loggerContent) {
			foreach (static::$fieldDescriptor->getFieldNames() as $k=>$f) {
				static::retrieveField($f);
			}
		}		
	}
			
	/**
	  * Set content for a single log field and store it in the associated LoggerContent object.
	  *
	  * @param string $field_name ID of log format field. 
	  * @param string $value Data to store.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function setFieldContent($field_name, $value) {
		if (static::$fieldDescriptor && static::$loggerContent) {
			static::retrieveField($field_name, $value);
		}
	}
	
	/**
	  * @return array Get array of DataRetrievalPolicy objects.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getDataRetrievalPolicies() {
		return static::$retrievalPolicies;
	}

	/**
	  * @param array $retrievalPolicies Array of DataRetrievalPolicy objects. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function setDataRetrievalPolicies($retrievalPolicies) {				
		if ($retrievalPolicies) {
			static::$retrievalPolicies= $retrievalPolicies;
		}		
	}

	
	/**
	  * Helper method to build complete URLs (might serve as an URI "prefix" providing protocol, ip-adress and port).
	  *
	  * @param boolean $use_forwarded_host Resolve host with the HTTP_X_FORWARDED_HOST (proxy) method (default is false).
	  * @return string URL origin, format: xyz://x.x.x.x:x/. 
	  *
	  * @author unknown source (stackoverflow.com), slightly modified
	  * @version v1.0.1
	  * @api
	  */
	public function getUrlOrigin($use_forwarded_host= false) {
		$s= $_SERVER;
		$ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
		$sp       = strtolower( $s['SERVER_PROTOCOL'] );
		$protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
		$port     = $s['SERVER_PORT'];
		$port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
		$host     = ( $use_forwarded_host && isset( $s['HTTP_X_FORWARDED_HOST'] ) ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
		$host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
		return $protocol . '://' . $host;
	}

}
?>
