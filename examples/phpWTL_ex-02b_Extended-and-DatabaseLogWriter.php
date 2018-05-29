<?php
/**
  * phpWTL example 2b
  *
  * Extended (W3C) Logger and DatabaseLogWriter (DBLW).
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.1
  */

use phpWTL\ExtendedLogger;
use phpWTL\DRP;
use phpWTL\DataRetrievalPolicy;
use phpWTL\DataRetrievalPolicyHelper;
use phpWTL\LogWriter\DBLW\DatabaseLogWriter;
use phpWTL\LogWriter\DBLW\DatabaseLogWriterHelper;
use phpWTL\FormatDescriptorHelper;

require_once '../ExtendedLogger.php';
require_once '../LogWriter/DBLW/DatabaseLogWriter.php';
require_once '../LogWriter/DBLW/DatabaseLogWriterHelper.php';
require_once '../FormatDescriptorHelper.php';

// instantiate a w3c extended logger
$logger= ExtendedLogger::getInstance();

// show your logger's format description (here: w3c extended)
echo "<br/>w3c extended format prefix: ";
echo $logger->getFormatDescriptor()->getformatPrefix();
echo "<br/>w3c extended format version: ";
echo $logger->getFormatDescriptor()->getformatVersion();
echo "<br/>w3c extended format field names: ";
print_r($logger->getFormatDescriptor()->getFieldNames());

// do the actual logging (data retrieval, validation and formatting)
$logger->log();

// define connection parameters for your database
$connectionParams = array(
    'dbname' => 'test',
    'user' => 'test',
    'password' => 'test',
    'host' => 'localhost',
    'port' => 3306,
    'charset' => 'utf8',
    'driver' => 'mysqli',
);

// define parameters database log writer
$writerParams = array(
	'table' => $logger->getFormatDescriptor()->getformatPrefix()."test_table",
	'safety' => DatabaseLogWriterHelper::SAFETY_NONE,
	'safe_naming_strategy' => DatabaseLogWriterHelper::SAFE_NAMING_STRATEGY_DBAL_ESCAPING
);

// instantiate a database log writer
$writer= new DatabaseLogWriter($connectionParams, $writerParams);

// define keys to be written and in which order:
// in this example we omit the "cs-username" field
$keys_to_show= $logger->getFormatDescriptor()->getRegularFieldNames();
$key_to_delete= array_search("cs-username", $keys_to_show);
if ($key_to_delete) unset($keys_to_show[$key_to_delete]);

// Update Extended format's "fields" directive
$logger->setDirectives($keys_to_show);

// don't update Extended format's "start-date" directive
$meta= $writer->fetchMetaDataFromDB();
if ($meta) $logger->getDataRetriever()->setFieldContent("dir_start-date", $meta["dir_start-date"]);

// get logger content object
$content_obj= $logger->getLoggerContent();

// write data to database
$regularData= $content_obj->toArrayTyped($keys_to_show);
$metaData= $content_obj->toArrayTypedMeta();
$writer->writeToLog($regularData, $metaData);

// show what has been written
echo "<br/><br/>toArray: "; print_r($regularData);
echo "<br/><br/>metaToArray: "; print_r($metaData);

?>
