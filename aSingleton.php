<?php
namespace phpWTL;

/**
  * Abstract class as a basis for Singleton.
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.0
  * @api
  */
abstract class aSingleton {
	/**
      * Array to hold instances of all derived classes.
      * @static
      */
	protected static $_instance= array();

	/**
      * Create new instance or give back already existing one.
      * @param array|mixed|null $inject  Can be used to inject one or more parameter(s) into the constructor
	  * @return $_instance[$class] The instance of a derived class
	  * @static
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
      */
	public static function getInstance($inject= null) {
	   $class= get_called_class();
	   if (false === array_key_exists($class, self::$_instance)) {
		   self::$_instance[$class]= new static($inject);
	   }
	   return self::$_instance[$class];
	}

	/** 
	  * Disable cloning for singleton.
	  * @throws Exception if called
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  */
	protected function __clone() { throw new Exception("no cloning for singleton"); }
	/** 
	  * Disable serialization for singleton.
	  * @throws Exception if called
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  */
	protected function __sleep() { throw new Exception("no serialization for singleton"); }
	/** 
	  * Disable de-serialization for singleton.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @throws Exception if called
	  */
	protected function __wakeup() { throw new Exception("no de-serialization for singleton"); }
	
	/**
      * Constructor stump.
	  *
      * @param object|null $inject Can be used to inject one or more parameter(s) into the constructor.
 	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
     */
	abstract protected function __construct($inject= null);
	
}
?>