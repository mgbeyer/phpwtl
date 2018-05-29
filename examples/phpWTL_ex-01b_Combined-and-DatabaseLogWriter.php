<?php
/**
  * phpWTL example 1b
  *
  * Combined Logger and DatabaseLogWriter (DBLW).
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.1
  */

use phpWTL\phpWTL;
use phpWTL\CombinedLogger;
use phpWTL\DRP;
use phpWTL\DataRetrievalPolicy;
use phpWTL\DataRetrievalPolicyHelper;
use phpWTL\LogWriter\DBLW\DatabaseLogWriter;
use phpWTL\LogWriter\DBLW\DatabaseLogWriterHelper;
use phpWTL\FormatDescriptorHelper;

require_once '../phpWTL.php';
require_once '../CombinedLogger.php';
require_once '../LogWriter/DBLW/DatabaseLogWriter.php';
require_once '../LogWriter/DBLW/DatabaseLogWriterHelper.php';
require_once '../FormatDescriptorHelper.php';


// custom policies example
$myPolicies= array(
	new DataRetrievalPolicy(
		array(
			'name' => DRP::DRP_CONTENT_LENGTH_RETRIEVAL, 
			'flag' => DRP::DRP_CLR_CUSTOM,
			'parameter' => 'Smiley.svg.png'
		)
	)
);

// instantiate a logger for "combined" format
$logger= CombinedLogger::getInstance($myPolicies);

// show your logger's format description (here: combined)
echo "<br/>combined format prefix: ";
echo $logger->getFormatDescriptor()->getformatPrefix();
echo "<br/>combined format field names: ";
print_r($logger->getFormatDescriptor()->getFieldNames());

// do the actual logging (disable formatter to prevent field delimiters in order to get the data type right!)
$logger->log(array("format" => false));

// individually change fields content after logging
$logger->getDataRetriever()->setFieldContent("user_id", "hello world!");

// define connection parameters for your database
$connectionParams= array(
    'dbname' => 'test',
    'user' => 'test',
    'password' => 'test',
    'host' => 'localhost',
    'port' => 3306,
    'charset' => 'utf8',
    'driver' => 'mysqli',
);

// define parameters for database log writer
$writerParams = array(
	'table' => $logger->getFormatDescriptor()->getformatPrefix()."test_table",
	'safety' => DatabaseLogWriterHelper::SAFETY_NONE,
	'safe_naming_strategy' => DatabaseLogWriterHelper::SAFE_NAMING_STRATEGY_DBAL_ESCAPING
);

// instantiate a database log writer
$writer= new DatabaseLogWriter($connectionParams, $writerParams);

// a writer will log their internal error, warnings and state into corr. variables:
// "error" array, "warning" array and "state" string
echo "<br/><br/>";
echo "database log writer ERRORS: ";
print_r($writer->error);
echo "<br/><br/>";
echo "database log writer WARNINGS: ";
print_r($writer->warning);
echo "<br/><br/>";
echo "database log writer state: ";
echo $writer->state;

// get logger content object
$content_obj= $logger->getLoggerContent();

// encoding example
$content_obj->setEncoding(phpWTL::SYSTEM_ENCODING);

// write data to database (raw datatypes)
$data= $content_obj->toArrayTyped();
$writer->writeToLog($data);

// show what has been written
echo "<br/><br/>toArray: "; print_r($data);

?>
