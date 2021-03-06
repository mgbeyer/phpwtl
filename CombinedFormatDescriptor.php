<?php
namespace phpWTL;
use phpWTL\CommonFormatDescriptor;

require_once 'CommonFormatDescriptor.php';

/**
  * Format descriptor for the combined log format (see: https://httpd.apache.org/docs/1.3/logs.html#combined). 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.0
  * @api
  */
class CombinedFormatDescriptor extends CommonFormatDescriptor {

	/**
	  * Set format prefix and create all additional format field descriptors in their proper sequence (array of DescriptorField objects):
	  *
	  * referrer, user_agent
	  * (appended to and in addition to those of common: host_ip, client_identity, user_id, timestamp, request_line, status_code, content-size)
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  */
	protected function __construct() {
		parent::__construct();

		static::$formatPrefix= "combined_";
		array_push(static::$formatFields, new DescriptorField(array('name' => 'referrer', 'prefix' => '"', 'suffix' => '"')));
		array_push(static::$formatFields, new DescriptorField(array('name' => 'user_agent', 'prefix' => '"', 'suffix' => '"')));
	}

}
?>