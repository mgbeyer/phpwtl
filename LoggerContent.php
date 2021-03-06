<?php
namespace phpWTL;
use phpWTL\FormatDescriptorHelper;

require_once 'FormatDescriptorHelper.php';

/**
  * Representation of the content of a single log entry/log event.
  *
  * This is a generic container class to store the field contents of a single logger entry. 
  * Instances of this class use dynamic getters and setters to deal with variable logging formats.
  * This container is capable of character encoding.
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.7
  * @api
  */
class LoggerContent {
	/** encoding */
	protected $_encoding= null;
	/** encoding assumption */
	protected $_encoding_assumption= null;
	/** encoding detection order */
	protected $_encoding_detection_order= null;
	/** datatype given (raw or formatted) */
	protected $_datatype_class= null;
	/** standard delimiter for toString methods */
	protected $_field_delimiter= null;
	/** store the format description object */
	protected $_format_descriptor= null;
	/** Store allowed field IDs */
	protected $_allowed_attributes= null;
	
	/**
	  * @param object $format_descriptor Provide a format descriptor class as the format blueprint. 
	  * @param array $params Provide parameter array ("field_delimiter", "encoding", "encoding_assumption, "encoding_detection_order")
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.3
	  * @api
	  */
   public function __construct($format_descriptor, $params= null) {
		if ($format_descriptor && $format_descriptor->getFieldNames()) {
			$this->_field_delimiter= $format_descriptor->getFormatFieldDelimiter();
			$this->_encoding_assumption= phpWTL::ENCODING_ASSUMPTION_SYSTEM;
			$this->_encoding_detection_order= phpWTL::DEFAULT_ENCODING_DETECTION_ORDER;
			if ($params && is_array($params)) {
				if (array_key_exists("field_delimiter", $params)) {
					$this->_field_delimiter= $params["field_delimiter"];
				}
				if (array_key_exists("encoding", $params)) {
					if ($params["encoding"] == phpWTL::SYSTEM_ENCODING) {
						$this->_encoding= phpWTL::getPhpDefaultEncoding();
					} else {
						$this->_encoding= $params["encoding"];
					}
				}
				if (array_key_exists("encoding_assumption", $params)) {
					$this->_encoding_assumption= $params["encoding_assumption"];
				}
				if (array_key_exists("encoding_detection_order", $params) && 
				    is_array($params["encoding_detection_order"]) && 
					!empty($params["encoding_detection_order"])) {
					$this->_encoding_detection_order= $params["encoding_detection_order"];
				}
				if (array_key_exists("datatype_class", $params)) {
					switch ($params["datatype_class"]) {
						case FormatDescriptorHelper::DATATYPE_FORMATTED:
							$this->_datatype_class= FormatDescriptorHelper::DATATYPE_FORMATTED;
						break;
						case FormatDescriptorHelper::DATATYPE_RAW:
						default:
							$this->_datatype_class= FormatDescriptorHelper::DATATYPE_RAW;
						break;
					}
				}
			}			
			$fields= $format_descriptor->getFieldNames();
			if ($fields && is_array($fields) && !empty($fields)) {
				$this->_allowed_attributes= $fields;
				$this->_format_descriptor= $format_descriptor;
				foreach($fields as $k => $v) {
					$this->{$v}= null;
				}
			}
		}
	}
	
	/**
	  * @return object Get the format descriptor class this LoggerContent object was built upon. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
    public function getFormatDescriptor() {
		if (!(null === $this->_format_descriptor)) return $this->_format_descriptor;
	}
	
	/**
	  * @return string Get the log field delimiter. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
    public function getFieldDelimiter() {
		return $this->_field_delimiter;
	}

	/**
	  * @param string $delimiter Set the log field delimiter. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
    public function setFieldDelimiter($delimiter= "") {
		$this->_field_delimiter= $delimiter;
	}

	/**
	  * @return string Get the datatype class setting. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
    public function getDatatypeClass() {
		return $this->_datatype_class;
	}

	/**
	  * @param int $datatype_class Set the datatype class. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
    public function setDatatypeClass($datatype_class= null) {
		switch ($datatype_class) {
			case FormatDescriptorHelper::DATATYPE_FORMATTED:
				$this->_datatype_class= FormatDescriptorHelper::DATATYPE_FORMATTED;
			break;
			case FormatDescriptorHelper::DATATYPE_RAW:
			default:
				$this->_datatype_class= FormatDescriptorHelper::DATATYPE_RAW;
			break;
		}
	}

	/**
	  * @return string Get the encoding setting. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
    public function getEncoding() {
		return $this->_encoding;
	}

	/**
	  * @param string $encoding Set the character encoding (null or empty to disable encoding). 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
    public function setEncoding($encoding= "") {
		if ($encoding == phpWTL::SYSTEM_ENCODING) {
			$this->_encoding= phpWTL::getPhpDefaultEncoding();
		} else {
			$this->_encoding= $encoding;
		}
	}

	/**
	  * @return string Get the encoding assumption. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
    public function getEncodingAssumption() {
		return $this->_encoding_assumption;
	}

	/**
	  * @param string $encoding_assumption Set the character encoding assumption. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
    public function setEncodingAssumption($encoding_assumption= "") {
		$this->_encoding_assumption= $encoding_assumption;
	}

	/**
	  * @return string Get the encoding detection order. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
    public function getEncodingDetectionOrder() {
		return $this->_encoding_detection_order;
	}

	/**
	  * @param string $encoding Set the character encoding (CSV string, null or empty to disable encoding). 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
    public function setEncodingDetectionOrder($order= null) {
		$this->_encoding_detection_order= $order;
	}

	/**
	  * Helper method to try to detect the encoding for a given format field
	  * @param string $key Name of attribute (format field). 
	  * @return string
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
    public function probeFieldEncoding($key) {
		$ret= null;
		
		if (extension_loaded("mbstring")) {
			$ret= mb_detect_encoding($val, $this->getEncodingDetectionOrder(), true);
		}
		
		return $ret;
	}
	
	/**
	  * Helper method to set the encoding for a given format field
	  * @param string $key Name of attribute (format field). 
	  * @param string $encoding_assumption The character encoding assumption/strategy. 
	  * @param string $encoding Character encoding. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
    public function setFieldEncoding($key, $encoding_assumption, $encoding) {
		if (in_array($key, $this->_allowed_attributes)) {
			$val= $this->{$key};
			
			if ($encoding == phpWTL::SYSTEM_ENCODING) {
				$encoding= phpWTL::getPhpDefaultEncoding();
			}
			switch ($encoding_assumption) {
				case phpWTL::ENCODING_ASSUMPTION_SYSTEM:
					// try to detect default PHP encoding
					$assumed_encoding= phpWTL::getPhpDefaultEncoding();
				break;
				case phpWTL::ENCODING_ASSUMPTION_PROBE_DATA:
					$assumed_encoding= null;
				break;
				default:
					$assumed_encoding= $encoding_assumption;
				break;
			}
			if (!$assumed_encoding) {
				// if mb extension is available, try to detect encoding from given attribute string
				$assumed_encoding= $this->probeFieldEncoding($f);
			}
			// if we can assume an encoding and iconv is available, encode return string
			if ($assumed_encoding && extension_loaded("iconv") && ($assumed_encoding != $encoding)) {
				$val= iconv($assumed_encoding, $encoding, $val);
			}
			
			$this->__set($key, $val);
		}
	}

	/**
	  * Helper method to set specific encoding for all attributes
	  * @param string $encoding_assumption The character encoding assumption/strategy. 
	  * @param string $encoding Character encoding. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
    public function straightenUpEncodingAll($encoding_assumption, $encoding) {
		if ($encoding_assumption && $encoding) {
			foreach ($this->_allowed_attributes as $k=>$f) {
				$this->setFieldEncoding($f, $encoding_assumption, $encoding);
			}
		}
	}

	/**
	  * Dynamic setter.
	  * @param string $key Name of attribute (format field) to set. 
	  * @param string $value Value for attribute (format field) to set. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function __set($key, $value) {
		if (in_array($key, $this->_allowed_attributes)) $this->{$key}= $value;
    }

	/**
	  * Dynamic getter.
	  * @param string $key Name of attribute (format field). 
	  * @return string Value of given attribute (format field). 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.2
	  * @api
	  */
    public function __get($key) {
		$ret= null;
		
    	if (in_array($key, $this->_allowed_attributes)) {
			$ret= $this->{$key};
			if ($this->getEncoding() && $this->getEncoding() != "") {
				switch ($this->getEncodingAssumption()) {
					case phpWTL::ENCODING_ASSUMPTION_SYSTEM:
						// try to detect default PHP encoding
						$assumed_encoding= phpWTL::getPhpDefaultEncoding();
					break;
					case phpWTL::ENCODING_ASSUMPTION_PROBE_DATA:
					break;
					default:
						$assumed_encoding= $this->getEncodingAssumption();
					break;
				}
				if (!$assumed_encoding) {
					// if mb extension is available, try to detect encoding from given attribute string
					$assumed_encoding= $this->probeFieldEncoding($key);
				}
				// if we can assume an encoding and iconv is available, encode return string
				if ($assumed_encoding && extension_loaded("iconv") && ($assumed_encoding != $this->getEncoding())) {
					$ret= iconv($assumed_encoding, $this->getEncoding(), $ret);
				}
			}
		}
		
		return $ret;
    }
	
	/**
	  * String representation of the log-entry content, all default fields, no meta fields, separated by the line delimiter (this is a typical logfile line).
	  * @return string
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.2.1
	  * @api
	  */
	public function __toString() {
		return $this->toString($this->_format_descriptor->getRegularFieldNames(FormatDescriptorHelper::DEFAULT_ONLY));
	}
	
	/**
	  * String representation of the log-entry content, regular (non-meta) fields according to whiteliste parameter, separated by the line delimiter (this is a typical logfile line).
	  * @param array $whitelist If given two things will be done: a) only fields matching the list will be included in the return array and b) the return array will be sorted according to the order of the whitelist.  
	  * @return string
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.2.3
	  * @api
	  */
	public function toString($whitelist= null) {
		$ret= null;
		
		if (!$whitelist) $whitelist= $this->_format_descriptor->getRegularFieldNames(FormatDescriptorHelper::DEFAULT_ONLY);
		$content= $this->toArray($whitelist);
		$ret= implode($this->_field_delimiter, array_values($content));
		
		return $ret;
	}

	/**
	  * String representation of the log-entry content meta fields according to whiteliste parameter, separated by the line delimiter.
	  * @param array $whitelist If given two things will be done: a) only fields matching the list will be included in the return array and b) the return array will be sorted according to the order of the whitelist.  
	  * @return string
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
	public function toStringMeta($whitelist= null) {
		$ret= null;
		
		if (!$whitelist) $whitelist= $this->_format_descriptor->getMetaFieldNames(FormatDescriptorHelper::DEFAULT_ONLY);
		$content= $this->toArray($whitelist);
		$ret= implode($this->_field_delimiter, array_values($content));
		
		return $ret;
	}

	/**
	  * Associative array representation of the regular (non-meta) log-entry content.
	  * @param array $whitelist If given two things will be done: a) only fields matching the list will be included in the return array and b) the return array will be sorted according to the order of the whitelist.  
	  * @return array
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
	public function toArrayTyped($whitelist= null) {
		return $this->toArrayComplex($whitelist, false, false);
	}
	/**
	  * Associative array representation of the regular (non-meta) log-entry content.
	  * @param array $whitelist If given two things will be done: a) only fields matching the list will be included in the return array and b) the return array will be sorted according to the order of the whitelist.  
	  * @return array
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
	public function toArrayTypedTrimmed($whitelist= null) {
		return $this->toArrayComplex($whitelist, true, false);
	}
	/**
	  * Associative array representation of the regular (non-meta) log-entry content.
	  * @param array $whitelist If given two things will be done: a) only fields matching the list will be included in the return array and b) the return array will be sorted according to the order of the whitelist.  
	  * @return array
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
	public function toArrayTypedMeta($whitelist= null) {
		return $this->toArrayComplex($whitelist, false, true);
	}
	/**
	  * Associative array representation of the regular (non-meta) log-entry content.
	  * @param array $whitelist If given two things will be done: a) only fields matching the list will be included in the return array and b) the return array will be sorted according to the order of the whitelist.  
	  * @return array
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
	public function toArrayTypedMetaTrimmed($whitelist= null) {
		return $this->toArrayComplex($whitelist, true, true);
	}

	/**
	  * Internal helper method for "toArrayTyped", "toArrayTypedMeta", "toArrayTypedTrimmed" and "toArrayTypedMetaTrimmed"
	  * @param array $whitelist If given two things will be done: a) only fields matching the list will be included in the return array and b) the return array will be sorted according to the order of the whitelist.  
	  * @param boolean $trimmed Call "toArrayTrimmed" or check if $meta is set and...
	  * @param boolean $meta Call "toArrayMeta" or just "toArray"
	  * @return array
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.3
	  */
	protected function toArrayComplex($whitelist, $trimmed, $meta) {
		$ret= array();
		
		if ($trimmed) {
			$content= $this->toArrayTrimmed($whitelist);
		} else {
			if ($meta) {
				$content= $this->toArrayMeta($whitelist);
			} else {
				$content= $this->toArray($whitelist);
			}
		}
		switch ($this->getDatatypeClass()) {
			case FormatDescriptorHelper::DATATYPE_FORMATTED:
				$types= $this->_format_descriptor->getFieldData("datatype_formatted", $whitelist);
			break;
			case FormatDescriptorHelper::DATATYPE_RAW:
			default:
				$types= $this->_format_descriptor->getFieldData("datatype_raw", $whitelist);
			break;
		}		
		if ($content && is_array($content) && $types && is_array($types)) {
			foreach ($content as $k=>$f) {
				$ret[$k]= array(
					"datatype" => $types[$k],
					"content" => $f
				);
			}
		}
		
		return $ret;
	}

	/**
	  * Associative array representation of the regular (non-meta) log-entry content.
	  * @param array $whitelist If given two things will be done: a) only fields matching the list will be included in the return array and b) the return array will be sorted according to the order of the whitelist.  
	  * @return array
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.3.2
	  * @api
	  */
	public function toArray($whitelist= null) {
		$ret= array();
		
		if (!$whitelist) $whitelist= $this->_format_descriptor->getRegularFieldNames(FormatDescriptorHelper::DEFAULT_ONLY);		
		// check if $whitlist contains only valid field names, delete invalid names
		if ($whitelist && is_array($whitelist)) {
			foreach ($whitelist as $k=>$f) {
				if (!in_array($f, $this->_allowed_attributes)) unset($whitelist[$k]);
			}
		}
		// only return fields contained in $whitelist
		foreach ($this->_allowed_attributes as $k=>$f) {
			if ($whitelist && is_array($whitelist)) {
				$pass= in_array($f, $whitelist);
			} else {
				$pass= true;
			}
			if ($pass) $ret[$f]= $this->__get($f);
		}
		// sort returned array according to $whitelist order
		if ($whitelist && is_array($whitelist)) $ret= $this->sortArray($ret, $whitelist);
		
		return $ret;
	}

	/**
	  * Associative array representation of the regular (non-meta) log-entry content, prefix+suffix trimmed.
	  * @param array $whitelist If given two things will be done: a) only fields matching the list will be included in the return array and b) the return array will be sorted according to the order of the whitelist.  
	  * @return array
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function toArrayTrimmed($whitelist= null) {
		$ret= $this->toArray($whitelist);

		foreach ($ret as $k=>$f) {
			$ret[$k]= $this->getFieldContentTrimmed($k);
		}
		
		return $ret;
	}

	/**
	  * Associative array representation of the log-entry meta content.
	  * @param array $whitelist If given two things will be done: a) only fields matching the list will be included in the return array and b) the return array will be sorted according to the order of the whitelist.  
	  * @return array
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.2
	  * @api
	  */
	public function toArrayMeta($whitelist= null) {
		$ret= array();
		
		if (!$whitelist) {
			$metas= $this->_format_descriptor->getMetaFieldNames(FormatDescriptorHelper::DEFAULT_ONLY);		
		} else {
			$metas= $this->_format_descriptor->getMetaFieldNames();	
		}
		// check if $whitlist contains only valid field names, delete invalid names
		if ($whitelist && is_array($whitelist)) {
			foreach ($whitelist as $k=>$f) {
				if (!in_array($f, $metas)) unset($whitelist[$k]);
			}
		}
		// only return fields contained in $whitelist
		foreach ($metas as $k=>$f) {
			if ($whitelist && is_array($whitelist)) {
				$pass= in_array($f, $whitelist);
			} else {
				$pass= true;
			}
			if ($pass) $ret[$f]= $this->__get($f);
		}
		// sort returned array according to $whitelist order
		if ($whitelist && is_array($whitelist)) $ret= $this->sortArray($ret, $whitelist);
		
		return $ret;
	}

	/**
	  * Sort an (associative) array by a sequence defined in another array.
	  * @param array $arrayToSort
	  * @param array $arrayToSortBy
	  * @return array
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function sortArray($arrayToSort, $arrayToSortBy) {
		$ret= array();
		if (is_array($arrayToSort) && is_array($arrayToSortBy) && $arrayToSort && $arrayToSortBy) {
			// check if $arrayToSortBy contains only valid key names (according to $arrayToSort), delete invalid names
			foreach ($arrayToSortBy as $k=>$f) {
				if (!array_key_exists($f, $arrayToSort)) unset($arrayToSortBy[$k]);
			}
			// sort
			$ret= array_merge(array_flip($arrayToSortBy), $arrayToSort);
		}
				
		return $ret;
	}

	/**
	  * Helper method to strip prefix/suffix from log field content.
	  *
	  * @param string $field_name ID of log format field. 
	  * @return string Trimmed field content
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
	public function getFieldContentTrimmed($field_name) {
		$fieldDescriptor= $this->getFormatDescriptor();
		if ($fieldDescriptor && $field_name) {
			$prefix= $fieldDescriptor->getPrefix($field_name);
			$suffix= $fieldDescriptor->getSuffix($field_name);
			return rtrim(ltrim($this->__get($field_name), $prefix), $suffix);
		}
	}

	/**
	  * Helper method to replace field names (IDs) with their captions for a given content array
	  * @param array $contentArray
	  * @return array
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function fieldNames2Captions($contentArray) {
		$ret= array();
		
		if ($contentArray && is_array($contentArray)) {
			$fieldDescriptor= $this->getFormatDescriptor();
			foreach ($contentArray as $k=>$f) {
				$caption= $fieldDescriptor->getCaption($k);
				if (!$caption) $caption= $k;
				$ret[$caption]= $f;
			}
		}
		
		return $ret;
	}

}
?>