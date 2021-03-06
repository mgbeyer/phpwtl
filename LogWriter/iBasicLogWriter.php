<?php
namespace phpWTL\LogWriter;

/**
  * Basic interface for a log writer. 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.0
  * @api All interface methods
  */
interface iBasicLogWriter {
	function writeToLog($entry);
}
?>
