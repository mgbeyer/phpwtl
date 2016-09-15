<?php
/**
  * phpWTL example 3a
  *
  * PHP application Logger and FileLogWriter (FLW).
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.0
  */

use phpWTL\PhpAppLogger;
use phpWTL\PhpAppLoggerHelper;
use phpWTL\LogWriter\FLW\FileLogWriter;
use phpWTL\LogWriter\FLW\FileLogWriterHelper;

require_once '../PhpAppLogger.php';
require_once '../PhpAppLoggerHelper.php';
require_once '../LogWriter/FLW/FileLogWriter.php';
require_once '../LogWriter/FLW/FileLogWriterHelper.php';

// instatiate an application logger, log-level threshold set to "WARNINGS"
$logger= PhpAppLogger::getInstance(PhpAppLoggerHelper::LOGLEVEL_WARNING);

// instantiate a file log writer
$writer= new FileLogWriter();

// a writer will log their internal error, warnings and state into corr. variables:
// "error" array, "warning" array and "state" string
echo "<br/><br/>";
echo "file log writer ERRORS: ";
print_r($writer->error);
echo "<br/><br/>";
echo "file log writer WARNINGS: ";
print_r($writer->warning);
echo "<br/><br/>";
echo "file log writer state: ";
echo $writer->state;

// define contents and parameters for a log-entry
$params= array(
	"loglevel" => PhpAppLoggerHelper::LOGLEVEL_ERROR,
	"message" => "Hello world! This logger has a {format_prefix} format prefix. This is call {count}.",
	"context" => array(
		"exception" => new RuntimeException('Hey dude, things went south BIG time!'),
		"format_prefix" => $logger->getFormatDescriptor()->getFormatPrefix(),
		"count" => 1,
		"data" => array("one" => 1, "two" => array("hello" => "2_1", "world" => "2_2"), "three" => 3),
		"data_2" => array("eins", "zwo", "drei"),
		"ex" => new Exception("DEBUG!!!"),
		"arr" => array("hallo" => "welt", "version" => $logger->getFormatDescriptor()->getFormatVersion())
	),
	"exclude_placeholders_from_context" => true
);

// actual logging
$fail= $logger->log($params);

if (!$fail) {
	// get logger content object
	$content_obj= $logger->getLoggerContent();

	// show what will be written...
	echo "<br/><br/>content __toString(): ".$content_obj;

	// if everything is ready, pass the content on to your writer.
	$writer->writeToLog($content_obj);
} else print_r($fail);

?>
