<?php
/**
  * phpWTL example 3b
  *
  * PHP application Logger and FileLogWriter (FLW) and LogBuffer.
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.0
  */

use phpWTL\PhpAppLogger;
use phpWTL\PhpAppLoggerHelper;
use phpWTL\LogWriter\FLW\FileLogWriter;
use phpWTL\LogWriter\FLW\FileLogWriterHelper;
use phpWTL\LogBuffer;
use phpWTL\LogBufferHelper;

require_once '../PhpAppLogger.php';
require_once '../PhpAppLoggerHelper.php';
require_once '../LogWriter/FLW/FileLogWriter.php';
require_once '../LogWriter/FLW/FileLogWriterHelper.php';
require_once '../LogBuffer.php';
require_once '../LogBufferHelper.php';

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

// establish a log buffer
// define how to use and feed the writer (i.e. store a single log entry) during the buffer flush loop
function mySimpleFlush($writer_object, $content_object) {
	$writer_object->writeToLog($content_object);
}
$myCallbacks= array (
	LogBufferHelper::CALLBACK_FLUSH_EACH => "mySimpleFlush"
);

// define buffer parameter
$myBufferParams= array (
	"buffer_size" => 4
);

// instatiate the log buffer
$logbuffer= new LogBuffer($logger, $writer, $myCallbacks, $myBufferParams);

// define contents and parameters for a log-entry
$params= array(
	"loglevel" => PhpAppLoggerHelper::LOGLEVEL_ERROR,
	"message" => "Hello world! This logger has a {format_prefix} format prefix. This is call {count}.",
	"context" => array(
		"exception" => new RuntimeException('Hey dude, things went south BIG time!'),
		"format_prefix" => $logger->getFormatDescriptor()->getFormatPrefix(),
		"nested_data" => array("one" => 1, "two" => array("hello" => "2_1", "world" => "2_2"), "three" => 3),
		"other_data" => array("eins", "zwo", "drei"),
		"ex" => new Exception("DEBUG!!!"),
		"arr" => array("hallo" => "welt", "version" => $logger->getFormatDescriptor()->getFormatVersion())
	),
	"exclude_placeholders_from_context" => true
);

// actual logging (a couple times for the demo)
for ($i=1; $i<4; $i++) {
	// a little variation for demo purposes
	if ($i>1) {
		$params["context"]= array();
		$params["message"]= "This is call {count}.";
	}
	$params["context"]["count"]= $i;
	$logbuffer->log($params);

	// sleep a random amount of seconds
	sleep(rand(1, 3));
	
	// just for demonstration/debugging purposes
	if ($logger->getLoggerContent()) {
		// get logger content object
		$content_obj= $logger->getLoggerContent();

		// show what will be written...
		echo "<br/><br/>content __toString(): ".$content_obj;
	}
}

// exception logging example
function inverse($x) {
    if (!$x) {
       throw new Exception('Division by zero.');
    }
    return 1/$x;
}

try {
	echo "division by zero: ".inverse(0);
} catch (Exception $e) {
	$logbuffer->log(array("loglevel" => PhpAppLoggerHelper::LOGLEVEL_CRITICAL, "message" => "Uh oh!", "context" => array("exception" => $e)));

	// just for demonstration/debugging purposes
	if ($logger->getLoggerContent()) {
		// get logger content object
		$content_obj= $logger->getLoggerContent();

		// show what will be written...
		echo "<br/><br/>content __toString(): ".$content_obj;
	}
}


?>
