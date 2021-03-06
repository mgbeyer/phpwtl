<?php
namespace phpWTL;

/**
  * Representation of a single logging format field.
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.4.1
  * @api
  */
class DescriptorField {
	/** @var string $name Field id. */
	public $name= null;
	/** @var string $caption Field title/appearance. */
	public $caption= null;
	/** @var string $prefix Field delimiter, start. */
	public $prefix= null;
	/** @var string $suffix Field delimiter, end. */
	public $suffix= null;
	/** @var string $formatter Format (conversion) specifications (e.g. for a timestamp). */
	public $formatter= null;
	/** @var string $validator Regular expression to validate field contents. */
	public $validator= null;
	/** @var string $datatype_raw type for raw data to use with a database log writer for mapping/abstraction layer purposes. Defaults to "string" if not set */
	public $datatype_raw= null;
	/** @var string $datatype_formatted type for formatted data to use with a database log writer for mapping/abstraction layer purposes. Defaults to "datatype_raw" if not set */
	public $datatype_formatted= null;
	/** @var string $default If the field should be omitted by default, set to "false". */
	public $default= null;
	/** @var string $meta If the field should be considered a format meta field, set to "true". */
	public $meta= null;

	/**
	  * @param array $param Associative array to provide all attributes. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.2.1
	  * @api
	  */
	public function __construct($param) {
		$this->name= array_key_exists('name', $param) ? $param['name'] : "";
		$this->caption= array_key_exists('caption', $param) ? $param['caption'] : "";
		$this->prefix= array_key_exists('prefix', $param) ? $param['prefix'] : "";
		$this->suffix= array_key_exists('suffix', $param) ? $param['suffix'] : "";
		$this->formatter= array_key_exists('formatter', $param) ? $param['formatter'] : "";
		$this->validator= array_key_exists('validator', $param) ? $param['validator'] : "";
		$this->datatype_raw= array_key_exists('datatype_raw', $param) ? $param['datatype_raw'] : "string";
		$this->datatype_formatted= array_key_exists('datatype_formatted', $param) ? $param['datatype_formatted'] : $this->datatype_raw;
		$this->default= array_key_exists('default', $param) ? ($param['default']==true) : true;
		$this->meta= array_key_exists('meta', $param) ? ($param['meta']==true) : false;
	}
}
?>