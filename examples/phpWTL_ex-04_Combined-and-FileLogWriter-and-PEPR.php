<?php
/**
  * phpWTL example 4
  *
  * Combined Logger and FileLogWriter (FLW) and P.E.P.R. tool.
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
use phpWTL\Tools\PEPR;
use phpWTL\Tools\PEPRHelper;
use phpWTL\LogBufferHelper;

define('PATH_TO_PHPWTL', '../phpWTL/');
require_once PATH_TO_PHPWTL.'phpWTL.php';
require_once PATH_TO_PHPWTL.'CombinedLogger.php';
require_once PATH_TO_PHPWTL.'LogWriter/FLW/FileLogWriter.php';
require_once PATH_TO_PHPWTL.'LogWriter/FLW/FileLogWriterHelper.php';
require_once PATH_TO_PHPWTL.'Tools/PEPR.php';
require_once PATH_TO_PHPWTL.'Tools/PEPRHelper.php';
require_once PATH_TO_PHPWTL.'LogBufferHelper.php';

// callback function for PEPR flush loop to set encoding for the logger content
// (if nothing special is needed, PEPR might be instantiated without callbacks, a simple callback is provided internally as a default)
function peprFlush($writer_object, $content_object) {
	$content_object->setEncoding(phpWTL::SYSTEM_ENCODING);
	$writer_object->writeToLog($content_object);
}
	
// set retrieval policy to enable PHP output buffering (needed for the PEPR tool)
$pol= array(
	new DataRetrievalPolicy(
		array(
			'name' => DRP::DRP_CONTENT_LENGTH_RETRIEVAL, 
			'flag' => DRP::DRP_CLR_BUFFER
		)
	)
);

// instantiate a logger for "combined" format
$logger= CombinedLogger::getInstance($pol);

// instantiate a file log writer
$writer= new FileLogWriter();

// define resource types (tag and attribute mappings) for PEPR
// Custom tags and/or multiple attribute definitions for resource are also possible.
// Resource attributes will be checked in the given sequence. 
// If a resource attribute is not found or contains an empty string, the next alternative will be evaluated.
// So it is possible to have different (custom) versions of the same tag (with different attribute signatures).
$restypes= array(
	'img' => array ('resource' => array('src')),
	'link' => array ('resource' => array('href')),
	'script' => array ('resource' => array('src')),
	'custom' => array ('resource' => array('src', 'alternative_attrib_for_src'))
);

// instantiate PEPR tool
$pepr= new PEPR($logger, $writer, $restypes, false, array (
	LogBufferHelper::CALLBACK_FLUSH_EACH => "peprFlush"
));
// alternative to instantiate PEPR tool with resource type definitions from an .ini file
$pepr= new PEPR($logger, $writer, PEPRHelper::getDatatypeMappingsFromIni("/my/stuff/my.ini"), false, array (
	LogBufferHelper::CALLBACK_FLUSH_EACH => "peprFlush"
));
// alternative: if nothing special is needed, PEPR might be instantiated without any callbacks, 
// a simple callback is provided internally as a default
$pepr= new PEPR($logger, $writer, $restypes);

// get static HTML generated from PHP output buffering for PEPR tool
$html= $logger->getBuffer();

// do the actual logging (data retrieval, validation and formatting) for this page
$logger->log();

// do stuff to the logger content, here: set encoding
$content_obj= $logger->getLoggerContent();
$content_obj->setEncoding(phpWTL::SYSTEM_ENCODING);

// write log entry for the current page hit
$writer->writeToLog($content_obj);

// finally let PEPR write log entries for all references to static assets found in the static HTML generated from PHP output buffering
$pepr->logAssets($html);

// PEPR was instantiated with disabled manual finalization (=automatic finalization), so this is commented-out
// if set to manual finalization, the actual logging process is done only after the finalize method is called
//$pepr->finalize();

?>
