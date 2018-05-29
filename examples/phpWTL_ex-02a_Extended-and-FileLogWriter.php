<?php
/**
  * phpWTL example 2a
  *
  * Extended (W3C) Logger and FileLogWriterEx (FLWEx).
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.1
  */

use phpWTL\ExtendedLogger;
use phpWTL\DRP;
use phpWTL\DataRetrievalPolicy;
use phpWTL\DataRetrievalPolicyHelper;
use phpWTL\LogWriter\FLW\FileLogWriterExt;

require_once '../ExtendedLogger.php';
require_once '../LogWriter/FLW/FileLogWriterExt.php';

// instantiate a w3c extended logger
$logger= ExtendedLogger::getInstance();

// show your logger's format description (here: w3c extended)
echo "<br/>w3c extended format prefix: ";
echo $logger->getFormatDescriptor()->getformatPrefix();
echo "<br/>w3c extended format field names: ";
print_r($logger->getFormatDescriptor()->getFieldNames());

// do the actual logging (data retrieval, validation and formatting)
$logger->log();

// instantiate a log writer
$writer= new FileLogWriterExt();

// define keys to be written and in which order:
// in this example we omit the "time" field and simply reverse the default field order
$keys_to_show= $logger->getFormatDescriptor()->getRegularFieldNames();
$key_to_delete= array_search("time", $keys_to_show);
if ($key_to_delete) unset($keys_to_show[$key_to_delete]);
$keys_to_show= array_reverse($keys_to_show);
$writer->writeToLogExt(
	$logger->getLoggerContent()->toString($keys_to_show), 
	// this needs to be done because of the "Fields" directive meta field, because we changed default keys and order
	$logger->buildDirectivesForFileWriter($keys_to_show)
);

// show what has been written
echo "<br/><br/>content toArray: ";
print_r($logger->getLoggerContent()->toArray($keys_to_show));
echo "<br/><br/>content directives: ";
print_r($logger->buildDirectivesForFileWriter($keys_to_show));

?>
