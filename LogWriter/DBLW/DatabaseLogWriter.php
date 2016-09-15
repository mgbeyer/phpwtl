<?php
namespace phpWTL\LogWriter\DBLW;
use phpWTL\phpWTL;
use phpWTL\LogWriter\iBasicLogWriter;
use phpWTL\LogWriter\DBLW\DatabaseLogWriterHelper;
use Doctrine\Common\ClassLoader;

define('DBAL_BASE_PATH', __DIR__.'/../../'.phpWTL::DBAL_FOLDER_NAME.'/');

require '/../iBasicLogWriter.php';
require 'DatabaseLogWriterHelper.php';
require DBAL_BASE_PATH.'lib/Doctrine/Common/ClassLoader.php';

/**
  * Database log writer (DBLW). 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.2.4
  * @api
  */
class DatabaseLogWriter implements iBasicLogWriter {
	/** Prefix for ini files. */
	const WRITER_PREFIX= "DBLW";
	/** default name for connection parameter ini file. */
	const CONN_PARAM_DEFAULT_INI= self::WRITER_PREFIX."-ConnParam.ini";
	/** default name for datatype mappings ini file. */
	const DATATYPE_MAPPINGS_DEFAULT_INI= self::WRITER_PREFIX."-DatatypeMappings.ini";
	/** Meta table suffix. */
	const META_SUFFIX= "_meta";

	protected $ready= false;
	public $state= null;
	public $error= null;
	public $warning= null;
	/** Currently not used */
	protected $timestampFormat= null;
	/** Currently not used */
	protected $log_timestamp= null;
	protected $dbconn= null;
	protected $_writerParams= null;
	protected $_datatypeMappings= null;
	protected $_datatypeMappingsDefault= null;
	
	/** 
	  * @param array $connectionParams Doctrine 2 DBAL connection parameter set
	  * @param array $writerParams "table" (string): Name for logs table (default = "access_log"). "safety" (int): Safety levels as defined in "DatabaseLogWriterHelper". "datatype_mappings_replace_defaults" (boolean): replace internal defaults entirely ("true") or make up/overwrite defaults ("false").
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function __construct($connectionParams, $writerParams= null) {
		$this->error= array();
		$this->warning= array();
		
		// Doctrine initialization
		$classLoader_main= new ClassLoader('Doctrine', DBAL_BASE_PATH.'lib');
		$classLoader_main->register();
		
		$this->initWriter($connectionParams, $writerParams);
	}


	/** 
	  * Initialize the writer.
	  *
	  * @param array $connectionParams Doctrine 2 DBAL connection parameter set
	  * @param array $writerParams See constructor
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.2.3
	  */
	protected function initWriter($connectionParams, $writerParams) {
		if ($connectionParams && is_array($connectionParams)) {
			// init db connection			
			try {
				$config= new \Doctrine\DBAL\Configuration();
				$this->dbconn= \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
			} catch (\Exception $e) {
				array_push($this->error, "constructor fail, db connection error: ".$e->getMessage());
			}
			if ($this->dbconn) {
				$this->ready= true;
				$this->state= "ok";
			}
		} else {
			array_push($this->error, "initWriter fail, bad connection parameters.");
		}

		if ($this->ready) {
			if (!array_key_exists("safe_naming_strategy", $writerParams)) {
				$writerParams["safe_naming_strategy"]= DatabaseLogWriterHelper::SAFE_NAMING_STRATEGY_WTL_CLEANSING;
			} else {
				if ($writerParams["safe_naming_strategy"]=="") $writerParams["safe_naming_strategy"]= DatabaseLogWriterHelper::SAFE_NAMING_STRATEGY_WTL_CLEANSING;
			}
			if (!array_key_exists("safety", $writerParams)) {
				$writerParams["safety"]= DatabaseLogWriterHelper::SAFETY_MAX;
			} else {
				if ($writerParams["safety"]=="") $writerParams["safety"]= DatabaseLogWriterHelper::SAFETY_MAX;
			}
			if (!array_key_exists("table", $writerParams)) {
				$writerParams["table"]= "access_log";
			} else {
				if ($writerParams["table"]=="") {
					$writerParams["table"]= "access_log";
				} else {
					$dbFriendlyName= self::dbFriendlyName($writerParams["table"], $writerParams["safe_naming_strategy"]);
					if ($writerParams["table"]!=$dbFriendlyName && $writerParams["safe_naming_strategy"]==DatabaseLogWriterHelper::SAFE_NAMING_STRATEGY_WTL_CLEANSING) {
						array_push($this->warning, "db table name (".$writerParams["table"].") was not ok, changed it to: ".$dbFriendlyName);
					}
				}
			}
			if (!array_key_exists("datatype_mappings", $writerParams)) {
				$writerParams["datatype_mappings"]= array();
			} else {
				if ($writerParams["datatype_mappings"]=="") $writerParams["datatype_mappings"]= array();
			}
			if (!array_key_exists("datatype_mappings_replace_defaults", $writerParams)) {
				$writerParams["datatype_mappings_replace_defaults"]= false;
			} else {
				if ($writerParams["datatype_mappings_replace_defaults"]=="") $writerParams["datatype_mappings_replace_defaults"]= false;
			}
			$this->_writerParams= $writerParams;
			
			$this->_datatypeMappingsDefault= array();
			$this->_datatypeMappings= array();

			if (
				!$writerParams["datatype_mappings_replace_defaults"] || 
				(!$writerParams["datatype_mappings"] || !is_array($writerParams["datatype_mappings"]))
				) {
					$this->initDatatypeMappingsDefault();
				}
			$this->initDatatypeMappings();
			
			$this->log_timestamp= date($this->timestampFormat);
		}
	}
	
	/** 
	  * Write data using DBAL QueryBuilder
	  *
	  * @param string $table Table name to write to
	  * @param array $fields The associative array containing all fields to write (representing a logfile entry), datatype and content.
	  * @param boolean update INSERT if false (default), UPDATE if true (update 'id' #1)
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  */
	private function writeToLogQB($table, $fields, $update= false) {
		$conn= $this->dbconn;
		$qb= $conn->createQueryBuilder();
		if ($update) {
			$qb->update($table);
		} else {
			$qb->insert($table);
		}		
		foreach ($fields as $k => $v) {
			$col= self::dbFriendlyName($k, $this->_writerParams["safe_naming_strategy"]);
			if ($k!="id") {
				$key= self::dbFriendlyName($k, DatabaseLogWriterHelper::SAFE_NAMING_STRATEGY_WTL_CLEANSING);
				if ($update) {
					$qb->set($col, ':'.$key);
				} else {
					$qb->setValue($col, ':'.$key);
				}
				$platform= $conn->getDatabasePlatform();
				$type= \Doctrine\DBAL\Types\Type::getType(static::getDBALDataType($v["datatype"]));
				$converted= $type->convertToDatabaseValue($type->convertToPHPValue($v["content"], $platform), $platform);
				$qb->setParameter($key, $converted);
			}
		}
		if ($update) $qb->where("id = 1");
		$qb->execute();
	}
	

	/** 
	  * Write log fields to a database
	  *
	  * @param array $regularFields The associative array containing all fields to write (representing a logfile entry), datatype and content.
	  * @param array $metaFields The associative array containing all meta fields to write (into a separate table).
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.2
	  * @api
	  */
	public function writeToLog($regularFields, $metaFields= null) {
		if ($regularFields && is_array($regularFields) && $this->ready) {
			$conn= $this->dbconn;	
			
			// add ID
			$regularFields["id"]= array("datatype" => "id", "content" => null);
			
			// structure
			$query= $this->getSchemaDiffQuery($regularFields, false, $this->_writerParams["safety"]);
			foreach ($query as $k => $q) {
				$this->dbconn->executeQuery($q);
			}
			
			// data
			$table= self::dbFriendlyName($this->_writerParams["table"], $this->_writerParams["safe_naming_strategy"]);
			$this->writeToLogQB($table, $regularFields);

			// meta data (insert or update single row only, no append)
			if ($metaFields && is_array($metaFields)) {
				$meta_table= self::dbFriendlyName($this->_writerParams["table"].static::META_SUFFIX, $this->_writerParams["safe_naming_strategy"]);
				
				// add ID
				$metaFields["id"]= array("datatype" => "id", "content" => null);
				
				// structure
				$query= $this->getSchemaDiffQuery($metaFields, true, $this->_writerParams["safety"]);
				foreach ($query as $k => $q) {
					$this->dbconn->executeQuery($q);
				}
				
				// data: check if there already, if no insert, if yes update
				$exists= $conn->executeQuery("SELECT * FROM ".$meta_table." WHERE id = 1")->fetch();
				if ($exists) {
					$this->writeToLogQB($meta_table, $metaFields, true);
				} else {
					$this->writeToLogQB($meta_table, $metaFields);
				}
			}
			
		}		
	}
				
	/** 
	  * Return data from meta table (if available, null otherwise)
	  *
	  * @return array
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function fetchMetaDataFromDB() {
		$ret= null;
		$conn= $this->dbconn;	
		$meta_table= $this->_writerParams["table"].static::META_SUFFIX;
		if ($conn->getSchemaManager()->tablesExist(array($meta_table))) {
			$ret= $conn->executeQuery("SELECT * FROM ".$meta_table." WHERE id=1")->fetch();
		}
		
		return $ret;
	}

	/** 
	  * Get SQL queries to update logger table
	  *
	  * @param array $fields Field names.
	  * @param boolean $meta If set fields are supposed to be stored in the meta table.
	  * @param boolean $dropsafe If set fields no longer used won't be dropped.
	  * @param boolean $changesafe If set fields no longer used won't be altered if datatype changes.
	  * @return array Queries neccessary for all changes
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.3.1
	  */
	protected function getSchemaDiffQuery($fields, $meta, $safety) {
		switch ($safety) {
			case DatabaseLogWriterHelper::SAFETY_NONE:
			case DatabaseLogWriterHelper::SAFETY_OFF:
				$dropsafe= false;
				$changesafe= false;
			break;
			case DatabaseLogWriterHelper::SAFETY_ALL:
			case DatabaseLogWriterHelper::SAFETY_MAX:
				$dropsafe= true;
				$changesafe= true;
			break;
			case DatabaseLogWriterHelper::SAFETY_DROP:
				$dropsafe= true;
				$changesafe= false;
			break;
			case DatabaseLogWriterHelper::SAFETY_CHANGE:
				$dropsafe= false;
				$changesafe= true;
			break;
		}
		
		$conn= $this->dbconn;		
		$platform= $conn->getDatabasePlatform();
		$sm= $conn->getSchemaManager();
		$comparator= new \Doctrine\DBAL\Schema\Comparator();
		$fromSchema= $sm->createSchema();
		$toSchema= clone $fromSchema;

		if ($meta) {
			$tablename= self::dbFriendlyName($this->_writerParams["table"].static::META_SUFFIX, $this->_writerParams["safe_naming_strategy"]);
		} else {
			$tablename= self::dbFriendlyName($this->_writerParams["table"], $this->_writerParams["safe_naming_strategy"]);
		}
		// check if table is already there
		if (!$toSchema->hasTable($tablename)) {
			$table= $toSchema->createTable($tablename);
		} else {
			$table= $toSchema->getTable($tablename);
		}
		
		// convert field names to db friendly version
		$db_friendly_fields= array();
		foreach ($fields as $k => $v) {
			$new_key= self::dbFriendlyName($k, $this->_writerParams["safe_naming_strategy"]);
			$db_friendly_fields[$new_key]= $v;
		}
		
		// drop fields no longer used, alter fields where attributes differ
		if (!$dropsafe || !$changesafe) {
			$columns= $table->getColumns();
			foreach ($columns as $c) {
				if (!$dropsafe) {
					if (!array_key_exists($c->getName(), $fields)) {
						$table->dropColumn(self::dbFriendlyName($c->getName(), $this->_writerParams["safe_naming_strategy"]));
					}
				}
				if (!$changesafe) {
					if (array_key_exists($c->getName(), $fields)) {
						$table->dropColumn(self::dbFriendlyName($c->getName(), $this->_writerParams["safe_naming_strategy"]));
					}
				}
			}
		}
			
		// add missing/new fields
		foreach ($db_friendly_fields as $k => $v) {
			if (!$table->hasColumn($k)) {
				$DBALmapping= $this->mapDBALDataType($v["datatype"]);
				$table->addColumn($k, $DBALmapping["datatype"], $DBALmapping["options"]);
			}
		}	
		
		// handle ID
		if ($table->hasColumn("id") && !$table->hasPrimaryKey()) {
			$table->setPrimaryKey(array("id"));
		}
		
		$schemaDiff= $comparator->compare($fromSchema, $toSchema);
		$queries= $schemaDiff->toSaveSql($platform);
		print_r($queries);
		// fix shitty inconsistent DBAL behavior when it comes to escaping (ADD works, DROP and CHANGE don't)!
		if ($this->_writerParams["safe_naming_strategy"] == DatabaseLogWriterHelper::SAFE_NAMING_STRATEGY_DBAL_ESCAPING) {
			foreach ($queries as $k => $q) {
				$queries[$k]= preg_replace_callback('/(DROP|CHANGE)( )([^,\s]+)/', function($m) {
					return $m[1].$m[2].self::dbFriendlyName($m[3], $this->_writerParams["safe_naming_strategy"]);
				}, $q);
			}
		}
		return $queries;
	}

	/** 
	  * Map the given basic (phpWTL) data type/alias to Doctrine DBAL conform data type and default portable options.
	  *
	  * @param string $datatype data type or alias, as set in a format descriptor
	  * @return array Doctrine DBAL data type (key: "datatype") and default portable options (key: "options"). Null if given type/alias is unknown/not supported
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function mapDBALDataType($datatype) {
		$ret= null;

		$type= static::getDBALDataType($datatype);
		if ($type) {
			$ret= array("datatype" => $type, "options" => static::getDataTypeDefaultsForDBAL($datatype));
		}
		
		return $ret;
	}

	/** 
	  * Get the Doctrine data type for a given basic (phpWTL) data type or alias.
	  *
	  * @param string $datatype data type or alias, as set in a format descriptor
	  * @return string Doctrine DBAL data type, null if given type/alias is unknown/not supported
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getDBALDataType($datatype) {
		$ret= null;
		
		foreach ($this->_datatypeMappings as $k=>$v) {
			$hit= false;
			if ($k==$datatype) {
				$hit= true;
			} else {
				if (array_key_exists('alias', $v)) {
					foreach ($v['alias'] as $kk=>$vv) {
						if ($vv==$datatype) {
							$hit= true;
						}
					}
				}
			}
			if ($hit) {
				if (array_key_exists('dbal_type', $v)) {
					$ret= $v['dbal_type'];
				} else {
					$ret= $k;
				}
			}
		}
		
		return $ret;
	}

	/** 
	  * Get the defaults (portable options) for a given basic (phpWTL) data type or alias.
	  *
	  * @param string $datatype data type or alias, as set in a format descriptor
	  * @return array default portable options, null if given type/alias is unknown/not supported or there are no options available
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getDataTypeDefaultsForDBAL($datatype) {
		$ret= array();
		
		foreach ($this->_datatypeMappings as $k=>$v) {
			$hit= false;
			if ($k==$datatype) {
				$hit= true;
			} else {
				if (array_key_exists('alias', $v)) {
					foreach ($v['alias'] as $kk=>$vv) {
						if ($vv==$datatype) {
							$hit= true;
						}
					}
				}
			}
			if ($hit) {
				if (array_key_exists('option', $v)) {
					$ret= $v['option'];
				}
			}
		}
		
		return $ret;
	}

	/** 
	  * Initialize the (custom) datatype mappings.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function initDatatypeMappings() {
		if ($this->_writerParams['datatype_mappings'] && is_array($this->_writerParams['datatype_mappings'])) {			
			foreach ($this->_writerParams['datatype_mappings'] as $k=>$v) {
				$this->_datatypeMappings[$k]= $v;
			}
		}
	}

	/** 
	  * Initialize the datatype mappings default.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.2
	  * @api
	  */
	public function initDatatypeMappingsDefault() {
		$this->_datatypeMappingsDefault= array (
			'id' => array (
				'dbal_type' => 'integer',
				'option' => array (
					'notnull' => true,
					'autoincrement' => true
				)
			),
			'integer' => array (
				'alias' => array ('smallint', 'int'),
				'option' => array (
					'notnull' => false
				)
			),
			'boolean' => array (
				'alias' => array ('bool')
			),
			'decimal' => array (
				'option' => array (
					'notnull' => false
				)
			),
			'float' => array (
				'option' => array (
					'notnull' => false
				)
			),
			'text' => array (
				'option' => array (
					'notnull' => false
				)
			),
			'date' => array (),
			'time' => array (),
			'datetime' => array (
				'alias' => array ('datetimetz')
			),
			'timestamp' => array (
				'dbal_type' => 'integer',
				'option' => array (
					'notnull' => true
				)
			),
			'string' => array (
				'alias' => array ('bigint'),
				'option' => array (
					'length' => '1024',
					'notnull' => false
				)
			)
		);
		$this->_datatypeMappings= $this->_datatypeMappingsDefault;
	}

	/** 
	  * Get the datatype mappings default.
	  *
	  * @return array
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getDatatypeMappingsDefault() {
		return $this->_datatypeMappingsDefault; 
	}

	/** 
	  * Get the datatype mappings in effect.
	  *
	  * @return array
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function getDatatypeMappings() {
		return $this->_datatypeMappings; 
	}

	/** Currently not used.
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	protected function getLogTimestamp() {
		return $this->log_timestamp; 
	}

	/** 
	  * Return cleansed (db name safe) version of given string, use "$strategy" to cleanse: 
	  * SAFE_NAMING_STRATEGY_WTL_CLEANSING (default): Least common denominator of a string used as a database table/column name (remove all characters other than a-z and underscore and convert to lowercase).
	  * SAFE_NAMING_STRATEGY_DBAL_ESCAPING: Doctrine DBAL escaping via "quoteIdentifier" method.
	  *
	  * @param string $name
	  * @param int $strategy Safe naming strategy
	  * @return string
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
	public function dbFriendlyName($name, $strategy) {
		switch ($strategy) {
			case DatabaseLogWriterHelper::SAFE_NAMING_STRATEGY_DBAL_ESCAPING:
				return $this->dbconn->quoteIdentifier($name);
			break;
			case DatabaseLogWriterHelper::SAFE_NAMING_STRATEGY_WTL_CLEANSING:
			default:
				return strtolower(preg_replace("/[^a-zA-Z_]/", "", $name));
			break;
			
		}		
	}

}

?>