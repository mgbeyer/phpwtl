<?php
namespace phpWTL\LogWriter\FLW;
use phpWTL\LogWriter\FLW\FileLogWriter;
use phpWTL\LogWriter\FLW\FileLogWriterHelper;

require_once 'FileLogWriter.php';

/**
  * File log writer extension for W3C Extended log file format (FLWext). Takes care of meta-data (directives). Might be used for other purposes as well, where header data must be written one-off at the beginning of a logfile.
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.2
  * @api
  */
class FileLogWriterExt extends FileLogWriter {

	/** 
	  * Write a log line to a file, write header information (e.g. directives for W3C Extended) at start if logfile doesn't exist yet.
	  *
	  * @param string $entry The string to write (representing a logfile line).
	  * @param mixed $directives The (single) string or array of strings to write.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  * @api
	  */
	public function writeToLogExt($entry, $directives) {
		if ($entry && $directives && $this->ready) {
			// convert single line string to array (easier for csv related functionality)
			if (is_array($directives)) {
				$dir= $directives;
			} else {
				$dir= array();
				$dir[0]= $directives;
			}
			$this->writeDirectivesToLog($dir);
			$this->writeToLog($entry);
		}
	}

	/** 
	  * Write extended directive lines to a file
	  *
	  * @param array $directives The strings to write.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  */
	protected function writeDirectivesToLog($directives) {
		if ($directives && is_array($directives) && $this->ready) {
			$saveLocation= $this->getLogfileLocation();
			if (!$this->existsLogfile()) {
				foreach ($directives as $k=>$f) {
					FileLogWriterHelper::writeToFile($saveLocation, $f.$this->_EOL, "a");
				}
			}
		}		
	}
	
}

?>