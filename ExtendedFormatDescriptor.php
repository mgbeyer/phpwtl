<?php
namespace phpWTL;
use phpWTL\aBasicFormatDescriptor;

require_once 'aBasicFormatDescriptor.php';

/**
  * Format descriptor for the W3C extended log file format (see: https://www.w3.org/TR/WD-logfile.html). 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.2
  * @api
  */
class ExtendedFormatDescriptor extends aBasicFormatDescriptor {
	
	/**
	  * Set format prefix and create all format field descriptors in their proper sequence (array of DescriptorField objects):
	  *
	  * date, time, c-ip, cs-username, s-computername, s-ip, s-port, cs-method, cs-uri-stem, cs-uri-query, sc-status, sc-bytes, time-taken, cs-version, cs-host, cs-user-agent, cs-cookie, cs-referrer, sc-substatus
	  * directives (meta): dir_version, dir_fields, dir_start-date, dir_software, dir_remark
	  *
	  * @param object|null $inject  Can be used to inject one or more parameter(s) into the constructor
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.5
	  */
	protected function __construct($inject= null) {
		static::$formatPrefix= "extended_";
		static::$formatFieldDelimiter= " ";
		static::$formatVersion= "1.0";
		static::$formatFields= array(
			new DescriptorField(array('name' => 'date', 'caption' => 'date', 'formatter' => '%Y-%m-%d', 'datatype_raw' => 'date')),
			new DescriptorField(array('name' => 'time', 'caption' => 'time', 'formatter' => '%H:%M:%S', 'datatype_raw' => 'time')),
			new DescriptorField(array('name' => 'c-ip', 'caption' => 'c-ip', 'formatter' => '', 'datatype_raw' => 'string')),
			new DescriptorField(array('name' => 'cs-username', 'caption' => 'cs-username', 'formatter' => '', 'datatype_raw' => 'string')),
			new DescriptorField(array('name' => 's-computername', 'caption' => 's-computername', 'formatter' => '', 'datatype_raw' => 'string', 'default' => false)),
			new DescriptorField(array('name' => 's-ip', 'caption' => 's-ip', 'formatter' => '', 'datatype_raw' => 'string')),
			new DescriptorField(array('name' => 's-port', 'caption' => 's-port', 'formatter' => '', 'datatype_raw' => 'integer')),
			new DescriptorField(array('name' => 'cs-method', 'caption' => 'cs-method', 'formatter' => '', 'datatype_raw' => 'string')),
			new DescriptorField(array('name' => 'cs-uri-stem', 'caption' => 'cs-uri-stem', 'formatter' => '', 'datatype_raw' => 'string')),
			new DescriptorField(array('name' => 'cs-uri-query', 'caption' => 'cs-uri-query', 'formatter' => '', 'datatype_raw' => 'string')),
			new DescriptorField(array('name' => 'sc-status', 'caption' => 'sc-status', 'formatter' => '', 'datatype_raw' => 'integer')),
			new DescriptorField(array('name' => 'sc-bytes', 'caption' => 'sc-bytes', 'formatter' => '', 'datatype_raw' => 'integer', 'default' => false)),
			new DescriptorField(array('name' => 'time-taken', 'caption' => 'time-taken', 'formatter' => '', 'datatype_raw' => 'integer', 'default' => false)),
			new DescriptorField(array('name' => 'cs-version', 'caption' => 'cs-version', 'formatter' => '', 'datatype_raw' => 'string', 'default' => false)),
			new DescriptorField(array('name' => 'cs-host', 'caption' => 'cs-host', 'formatter' => '', 'datatype_raw' => 'string', 'default' => false)),
			new DescriptorField(array('name' => 'cs-user-agent', 'caption' => 'cs(User-Agent)', 'formatter' => '', 'datatype_raw' => 'string')),
			new DescriptorField(array('name' => 'cs-cookie', 'caption' => 'cs(Cookie)', 'formatter' => '', 'datatype_raw' => 'string', 'default' => false)),
			new DescriptorField(array('name' => 'cs-referrer', 'caption' => 'cs(Referrer)', 'formatter' => '', 'datatype_raw' => 'string', 'default' => false)),
			new DescriptorField(array('name' => 'sc-substatus', 'caption' => 'sc-substatus', 'formatter' => '', 'datatype_raw' => 'string')),
			// meta fields (directives)
			new DescriptorField(array('name' => 'dir_version', 'caption' => '#Version', 'formatter' => '', 'datatype_raw' => 'string', 'meta' => true)),
			new DescriptorField(array('name' => 'dir_fields', 'caption' => '#Fields', 'formatter' => '', 'datatype_raw' => 'string', 'meta' => true)),
			new DescriptorField(array('name' => 'dir_start-date', 'caption' => '#Start-Date', 'formatter' => '%Y-%m-%d %H:%M:%S', 'datatype_raw' => 'datetime', 'meta' => true)),
			new DescriptorField(array('name' => 'dir_software', 'caption' => '#Software', 'formatter' => '', 'datatype_raw' => 'string', 'meta' => true)),
			new DescriptorField(array('name' => 'dir_remark', 'caption' => '#Remark', 'formatter' => '', 'datatype_raw' => 'string', 'meta' => true))
		);
	}
	
}
?>