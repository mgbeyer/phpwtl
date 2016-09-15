<?php
namespace phpWTL;
use phpWTL\aBasicFormatDescriptor;

require_once 'aBasicFormatDescriptor.php';

/**
  * Format descriptor for the NCSA common log format (see: https://en.wikipedia.org/wiki/Common_Log_Format). 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.3
  * @api
  */
class CommonFormatDescriptor extends aBasicFormatDescriptor {
	
	/**
	  * Set format prefix and create all format field descriptors in their proper sequence (array of DescriptorField objects):
	  *
	  * host_ip, client_identity, user_id, timestamp, request_line, status_code, content-size
	  *
	  * @param object|null $inject  Can be used to inject one or more parameter(s) into the constructor
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.6
	  */
	protected function __construct($inject= null) {
		static::$formatPrefix= "common_";
		static::$formatFieldDelimiter= " ";
		static::$formatFields= array(
			new DescriptorField(array('name' => 'host_ip')),
			new DescriptorField(array('name' => 'client_identity')),
			new DescriptorField(array('name' => 'user_id')),
			new DescriptorField(array('name' => 'timestamp', 'prefix' => '[', 'suffix' => ']', 'formatter' => '%d/%b/%Y:%H:%M:%S %z', 'datatype_raw' => 'datetime', 'datatype_formatted' => 'string')),
			new DescriptorField(array('name' => 'request_line', 'prefix' => '"', 'suffix' => '"')),
			new DescriptorField(array('name' => 'status_code', 'datatype' => 'integer')),
			new DescriptorField(array('name' => 'content_size', 'formatter' => '%b', 'datatype_raw' => 'integer', 'datatype_formatted' => 'string'))
		);
	}
	
}
?>