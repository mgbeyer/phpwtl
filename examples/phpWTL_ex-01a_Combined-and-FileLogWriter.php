<?php
/**
  * phpWTL example 1a
  *
  * Combined Logger and FileLogWriter (FLW).
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.0
  */

use phpWTL\phpWTL;
use phpWTL\CombinedLogger;
use phpWTL\CommonCombinedDRP;
use phpWTL\DataRetrievalPolicy;
use phpWTL\DataRetrievalPolicyHelper;
use phpWTL\LogWriter\FLW\FileLogWriter;
use phpWTL\LogWriter\FLW\FileLogWriterHelper;

require_once '../phpWTL.php';
require_once '../CombinedLogger.php';
require_once '../LogWriter/FLW/FileLogWriter.php';
require_once '../LogWriter/FLW/FileLogWriterHelper.php';


// custom policies example
$myPolicies= array(
	new DataRetrievalPolicy(
		array(
			'name' => CommonCombinedDRP::DRP_CC_CONTENT_LENGTH_RETRIEVAL, 
			'flag' => CommonCombinedDRP::DRP_CC_CLR_CUSTOM,
			'parameter' => 'phpWTL_ex-01_Combined-and-FileLogWriter.php'
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

// override field format example
$logger->getFormatDescriptor()->setFormatter("content_size", "%B");

// do the actual logging (data retrieval, validation and formatting)
$logger->log();

// you can individually change fields content after logging
// (but then you might have to apply validator or formatter yourself afterwards if needed)
$myval= "hello world!";
if ($logger->getDataValidator()->isValid("user_id", $myval)) {
	$logger->getDataRetriever()->setFieldContent("user_id", $myval);
	$logger->getDataFormatter()->formatAllField("user_id");
}

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

// get logger content object
$content_obj= $logger->getLoggerContent();

// encoding example
$content_obj->setEncoding(phpWTL::SYSTEM_ENCODING);

// show what will be written...
echo "<br/><br/>content __toString(): ".$content_obj;

// if everything is ready, pass the content on to your writer.
$writer->writeToLog($content_obj);

?>
