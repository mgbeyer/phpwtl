<?php
namespace phpWTL;
use phpWTL\aBasicFormatDescriptor;

require_once 'aBasicFormatDescriptor.php';

/**
  * Format descriptor for PHP event/error/exception logging. 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.2
  * @api
  */
class PhpAppFormatDescriptor extends aBasicFormatDescriptor {
	
	/**
	  * Set format prefix and create all format field descriptors in their proper sequence (array of DescriptorField objects):
	  *
	  * timestamp, message, loglevel_caption, loglevel, context_data
	  *
	  * @param object|null $inject  Can be used to inject one or more parameter(s) into the constructor
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.2
	  */
	protected function __construct($inject= null) {
		static::$formatPrefix= "php_";
		static::$formatFieldDelimiter= " ";
		static::$formatFields= array(
			new DescriptorField(array('name' => 'timestamp', 'formatter' => '%Y-%m-%d %H:%M:%S', 'datatype_raw' => 'datetime')),
			new DescriptorField(array('name' => 'message')),
			new DescriptorField(array('name' => 'loglevel_caption')),
			new DescriptorField(array('name' => 'loglevel', 'datatype_raw' => 'integer')),
			new DescriptorField(array('name' => 'context_data', 'datatype_raw' => 'text')),
		);
	}
	
}
?>