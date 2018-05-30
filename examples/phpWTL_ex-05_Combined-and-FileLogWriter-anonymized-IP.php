<?php
/**
  * phpWTL example 5
  *
  * Combined Logger and FileLogWriter (FLW) with anonymized IP addresses.
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.0
  */

use phpWTL\phpWTL;
use phpWTL\CombinedLogger;
use phpWTL\DRP;
use phpWTL\DataRetrievalPolicy;
use phpWTL\DataRetrievalPolicyHelper;
use phpWTL\LogWriter\FLW\FileLogWriter;
use phpWTL\LogWriter\FLW\FileLogWriterHelper;
use phpWTL\Tools\ipTools;

define('PATH_TO_PHPWTL', '../phpWTL/');
require_once PATH_TO_PHPWTL.'phpWTL.php';
require_once PATH_TO_PHPWTL.'CombinedLogger.php';
require_once PATH_TO_PHPWTL.'LogWriter/FLW/FileLogWriter.php';
require_once PATH_TO_PHPWTL.'LogWriter/FLW/FileLogWriterHelper.php';
require_once PATH_TO_PHPWTL.'Tools/ipTools.php';


// instantiate a logger for "combined" format
$logger= CombinedLogger::getInstance();

// instantiate a file log writer
$writer= new FileLogWriter();

// do the actual logging (data retrieval, validation and formatting)
$logger->log();

// anonymize IP address
$ip_orig= $logger->getLoggerContent()->__get("host_ip");
// "ipAnon" might be called without $params if the default is convenient for you 
// (the array below represents the default)
// alternative call would be: $ip= ipTools::ipAnon($ip_orig);
$ip= ipTools::ipAnon($ip_orig, array(
	'ip4cut' => "1", 		// anonymize last 1/4 of ip4
	'ip6cut' => "5", 		// anonymize last 5/8 of ip6
	'ip4wildcard' => "0", 	// replace anonymized parts with 0
	'ip6wildcard' => ""		// replace anonymized parts with empty string
));
if ($logger->getDataValidator()->isValid("host_ip", $ip)) {
	$logger->getDataRetriever()->setFieldContent("host_ip", $ip);
	$logger->getDataFormatter()->formatAllField("host_ip");
}

// write log entry
$writer->writeToLog($logger->getLoggerContent());
?>
