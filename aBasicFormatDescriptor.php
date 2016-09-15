<?php
namespace phpWTL;
use phpWTL\aSingleton;
use phpWTL\FormatDescriptorHelper;
use phpWTL\DescriptorField;

require_once 'aSingleton.php';
require_once 'FormatDescriptorHelper.php';
require_once 'DescriptorField.php';

/**
  * Abstract logging format descriptor class. 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.3.1
  * @api
  */
abstract class aBasicFormatDescriptor extends aSingleton {
	protected static $formatFields= null;
	protected static $formatPrefix= null;
	protected static $formatVersion= null;
	protected static $formatFieldDelimiter= null;
	

	/**
	  * @param $default lists all or only fields (not) flagged as default
	  * @return array Get array containing all log format field IDs in their proper sequence. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.2.2
	  * @api
	  */
	public function getFieldNames($default= FormatDescriptorHelper::DEFAULT_ONLY) {
		$ret= array();
		foreach (static::$formatFields as $k=>$f) {
			switch ($default) {
				case FormatDescriptorHelper::DEFAULT_ONLY:
					$add= $f->default;
				break;
				case FormatDescriptorHelper::DEFAULT_NONE:
					$add= !$f->default;
				break;
				case FormatDescriptorHelper::DEFAULT_ANY:
				default:
					$add= true;
				break;	
			}
			if ($add) array_push($ret, $f->name);
		}
		return $ret;
	}

	/**
	  * @param $default lists all or only fields (not) flagged as default
	  * @return array Get array containing all log format meta field IDs in their proper sequence. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.2.1
	  * @api
	  */
	public function getMetaFieldNames($default= FormatDescriptorHelper::DEFAULT_ONLY) {
		$ret= array();
		foreach (static::$formatFields as $k=>$f) {
			switch ($default) {
				case FormatDescriptorHelper::DEFAULT_ONLY:
					$add= $f->default;
				break;
				case FormatDescriptorHelper::DEFAULT_NONE:
					$add= !$f->default;
				break;
				case FormatDescriptorHelper::DEFAULT_ANY:
				default:
					$add= true;
				break;	
			}
			if ($add && $f->meta) array_push($ret, $f->name);
		}
		return $ret;
	}

	/**
	  * @param $default lists all or only fields (not) flagged as default
	  * @return array Get array containing all regular (non-meta) log format field IDs in their proper sequence. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.2.2
	  * @api
	  */
	public function getRegularFieldNames($default= FormatDescriptorHelper::DEFAULT_ONLY) {
		$ret= array();
		foreach (static::$formatFields as $k=>$f) {
			switch ($default) {
				case FormatDescriptorHelper::DEFAULT_ONLY:
					$add= $f->default;
				break;
				case FormatDescriptorHelper::DEFAULT_NONE:
					$add= !$f->default;
				break;
				case FormatDescriptorHelper::DEFAULT_ANY:
				default:
					$add= true;
				break;	
			}
			if ($add && !$f->meta) array_push($ret, $f->name);
		}
		return $ret;
	}

	/**
	  * @param string $data Attribute to retrieve
	  * @param array $names Array of field names (IDs)
	  * @return array Get associative array containing all log format field data for a given list and attribute in their proper sequence. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
	public function getFieldData($data, $names= null) {
		$ret= array();
		foreach (static::$formatFields as $k=>$f) {
			if ($names && is_array($names)) {
				$pass= in_array($f->name, $names);
			} else {
				$pass= true;
			}
			if ($pass && $data) {
				$ret[$f->name]= static::getFieldAttribute($f->name, $data);
			}
		}
		return $ret;
	}

	/**
	  * @param string $field_name ID of log format field. 
	  * @return object DescriptorField. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getFieldDescription($field_name) {
		foreach (static::$formatFields as $k=>$f) {
			if ($f->name==$field_name) return $f;
		}
	}

	/**
	  * @return array Get array of all DescriptorField attribute names: name, prefix, suffix, formatter, validator, datatype. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getFieldAttributeNames() {
		return array_keys(get_object_vars(static::$formatFields[0]));
		
	}
	
	/**
	  * @param string $field_name ID of log format field. 
	  * @param string $attribute_name Name of the DescriptorField attribute. 
	  * @return string Value of the DescriptorField attribute for the desired field ID. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getFieldAttribute($field_name, $attribute_name) {
		foreach (static::$formatFields as $k=>$f) {
			if ($f->name==$field_name && property_exists($f, $attribute_name)) return $f->{$attribute_name};
		}
	}
	
	/**
	  * @param string $field_name ID of log format field. 
	  * @param string $attribute_name Name of the DescriptorField attribute ("name", because it serves as an ID, is immutable). 
	  * @param string $value Value to assign the DescriptorField attribute for the desired field ID. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function setFieldAttribute($field_name, $attribute_name, $value) {
		if ($attribute_name!="name" && in_array($attribute_name, static::getFieldAttributeNames())) {
			foreach (static::$formatFields as $k=>$f) {
				if ($f->name==$field_name) {
					$f->{$attribute_name}= $value;
				}
			}
		}
	}

	/**
	  * @return string Prefix (ID) for the log format this descriptor class represents, suffixed by an underscore. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getFormatPrefix() {
		return static::$formatPrefix;
	}

	/**
	  * @return string Line delimiter for a "toString-like" representation of all format fields content. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getFormatFieldDelimiter() {
		return static::$formatFieldDelimiter;
	}

	/**
	  * @return string Version for the log format this descriptor class represents. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getFormatVersion() {
		return static::$formatVersion;
	}

	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getCaption($field_name) {
		return static::getFieldAttribute($field_name, "caption");
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getPrefix($field_name) {
		return static::getFieldAttribute($field_name, "prefix");
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getSuffix($field_name) {
		return static::getFieldAttribute($field_name, "suffix");
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getFormatter($field_name) {
		return static::getFieldAttribute($field_name, "formatter");
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getValidator($field_name) {
		return static::getFieldAttribute($field_name, "validator");
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getDatatypeRaw($field_name) {
		return static::getFieldAttribute($field_name, "datatype_raw");
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getDatatypeFormatted($field_name) {
		return static::getFieldAttribute($field_name, "datatype_formatted");
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function isDefault($field_name) {
		return static::getFieldAttribute($field_name, "default");
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function isMeta($field_name) {
		return static::getFieldAttribute($field_name, "meta");
	}

	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function setCaption($field_name, $value) {
		static::setFieldAttribute($field_name, "caption", $value);
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function setPrefix($field_name, $value) {
		static::setFieldAttribute($field_name, "prefix", $value);
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function setSuffix($field_name, $value) {
		static::setFieldAttribute($field_name, "suffix", $value);
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function setFormatter($field_name, $value) {
		static::setFieldAttribute($field_name, "formatter", $value);
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function setValidator($field_name, $value) {
		static::setFieldAttribute($field_name, "validator", $value);
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function setDatatypeRaw($field_name, $value) {
		static::setFieldAttribute($field_name, "datatype_raw", $value);
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function setDatatypeFormatted($field_name, $value) {
		static::setFieldAttribute($field_name, "datatype_formatted", $value);
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function setDefault($field_name, $on= true) {
		$value= ($on==true);
		static::setFieldAttribute($field_name, "default", $value);
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function setMeta($field_name, $on= true) {
		$value= ($on==true);
		static::setFieldAttribute($field_name, "meta", $value);
	}
	
}
?>