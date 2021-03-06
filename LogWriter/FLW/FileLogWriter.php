<?php
namespace phpWTL\LogWriter\FLW;
use phpWTL\LogWriter\iBasicLogWriter;
use phpWTL\LogWriter\FLW\FileLogWriterHelper;

require_once __DIR__ .'/../iBasicLogWriter.php';
require_once 'FileLogWriterHelper.php';

/**
  * File log writer (FLW). 
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.10
  * @api
  */
class FileLogWriter implements iBasicLogWriter {
	/** Prefix for ini files. */
	const WRITER_PREFIX= "FLW";
	/** Path for main configuration file. */
	const SETTINGS_INI_PATH= "../../config/FLW-default.ini";
	/** Path for credentials configuration file. */
	const USER_INI_PATH= "../../config/FLW-default-cred.ini";
	/** Minimum length for a password. */
	const PW_STRENGTH= 8;

	protected $ready= false;
	public $state= null;
	public $error= null;
	public $warning= null;
	protected $eolMethod= null;
	protected $eolSequence= null;
	protected $_EOL= null;
	protected $logsPath= null;
	protected $baseName= null;
	protected $timestampFormat= null;
	protected $rotationPolicy= null;
	protected $loginUser= null;
	protected $loginPwHash= null;
	protected $htaccessProtection= null;
	protected $log_timestamp= null;

	protected $document_root= null;
	//protected $script_path= null;
	protected $full_logs_path= null;
	
	/** 
	  * @param string $inifile Name/path for custom configuration file.
	  * @example "./LogWriter/FLW/FLW-default.ini" See "FLW-default.ini" for a detailed description.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.3
	  * @api
	  */
	public function __construct($inifile= null) {
		$this->error= array();
		$this->warning= array();
		$this->validateIniKey("eol_sequence");
		$this->validateIniKey("eol_method");
		$this->validateIniKey("htaccess_protection");
		$this->validateIniKey("logs_path");
		$this->validateIniKey("base_name");
		$this->validateIniKey("rotation_policy");

		$this->document_root= FileLogWriterHelper::pathHelperHarmonizeTrailingSeparator($_SERVER['DOCUMENT_ROOT']);
		$this->full_logs_path= FileLogWriterHelper::sanitizePath($this->document_root.$this->logsPath);
		
		$this->initWriter($inifile);
	}


	/** 
	  * Initialize the writer.
	  *
	  * @param string $inifile Config. file.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.8
	  */
	protected function initWriter($inifile= null) {
		$this->ready= true;
		$this->state= "ok";

		if ($inifile) {
			// read individual ini			
			$parts= pathinfo(FileLogWriterHelper::sanitizePath($inifile));			
			if (!FileLogWriterHelper::pathLeavesOrEqualsRoot($parts["dirname"], $this->document_root)) {
				$path= FileLogWriterHelper::sanitizePath($this->document_root.$parts["dirname"]);
				$settings_path= $path.FileLogWriterHelper::sanitizeFilename($parts["basename"]);
				$cred_path= $path.FileLogWriterHelper::sanitizeFilename($parts["filename"])."-cred.".$parts["extension"];
			}
		} else {
			// attempt to read default ini
			$settings_path= __DIR__.FileLogWriterHelper::FOLDER_SEPARATOR.static::SETTINGS_INI_PATH;
			$cred_path= __DIR__.FileLogWriterHelper::FOLDER_SEPARATOR.static::USER_INI_PATH;
		}
				
		// settings
		if (file_exists($settings_path)) {
			$config= array_change_key_case(parse_ini_file($settings_path), CASE_LOWER);
		} else {
			$config= null;
			array_push($this->warning, "failed to open settings file, ".$settings_path);
		}
		if ($config && is_array($config)) {
			if (array_key_exists("eol_sequence", $config)) $this->validateIniKey("eol_sequence", $config["eol_sequence"]);
			if (array_key_exists("eol_method", $config)) $this->validateIniKey("eol_method", $config["eol_method"]);
			if (array_key_exists("htaccess_protection", $config)) $this->validateIniKey("htaccess_protection", $config["htaccess_protection"]);
			if (array_key_exists("logs_path", $config)) $this->validateIniKey("logs_path", $config["logs_path"]);
			if (array_key_exists("base_name", $config)) $this->validateIniKey("base_name", $config["base_name"]);
			if (array_key_exists("rotation_policy", $config)) $this->validateIniKey("rotation_policy", $config["rotation_policy"]);
		} else {
			array_push($this->warning, "failed to parse settings file, ".$settings_path);
		}
		
		// credentials
		if ($this->htaccessProtection=="on") {
			if (file_exists($cred_path)) {
				$credentials= array_change_key_case(parse_ini_file($cred_path), CASE_LOWER);
				$err= 0;
				if (array_key_exists("user", $credentials)) {
					$err+= $this->validateIniKey("user", $credentials["user"]);
				} else $err+= -1;
				if (array_key_exists("password", $credentials)) {
					$err+= $this->validateIniKey("password", $credentials["password"]);
				} else $err+= -1;
				if ($err<>0) $credentials= null;
			} else {
				$credentials= null;
				array_push($this->warning, "failed to open credentials file, htaccess_protection=".$this->htaccessProtection.", ".$cred_path.", using .htpasswd if applicable.");
			}
			if ($credentials && is_array($credentials)) {
				unlink($cred_path);
				if (file_exists($this->full_logs_path)) {
					if (file_exists($this->full_logs_path.".htaccess")) unlink($this->full_logs_path.".htaccess");
					if (file_exists($this->full_logs_path.".htpasswd")) unlink($this->full_logs_path.".htpasswd");
				}
			} else {
				if (!file_exists($this->full_logs_path.".htaccess") || !file_exists($this->full_logs_path.".htpasswd")) {
					$this->state= "fail: .htaccess protection is set but credentials (user and password) are not provided!";
					$this->ready= false;
				}
			}
		}
		if ($this->ready) {
			$this->prepareLogDirectory();
			if ($this->htaccessProtection=="on") $this->prepareHtaccessProtection();			
			$this->log_timestamp= date($this->timestampFormat);
		}
	}
	
	/** 
	  * Write a log line to a file
	  *
	  * @param string $entry The string to write (representing a logfile line).
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.6
	  * @api
	  */
	public function writeToLog($entry) {
		if ($entry && $this->ready) {
			$saveLocation= $this->getLogfileLocation();
			$ok= FileLogWriterHelper::writeToFile($saveLocation, $entry.$this->_EOL, "a");
			if (!$ok) array_push($this->error, "writeToLog failed.");
		}		
	}
	
	/** 
	  * Check if logfile exists
	  *
	  * @return boolean
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function existsLogfile() {
		return file_exists($this->getLogfileLocation());
	}

	/** 
	  * Get logfile location
	  *
	  * @return string
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  */
	protected function getLogfileLocation() {
		$timestamp= $this->getLogTimestamp(); 
		$logfile= $this->baseName.".".$timestamp; 
		return $this->full_logs_path.$logfile;
	}

	/** 
	  * Create .htacces and .htpasswd to protect logs directory (if configured).
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.4
	  */
	protected function prepareHtaccessProtection() {
		if (!file_exists($this->full_logs_path.".htaccess") || !file_exists($this->full_logs_path.".htpasswd")) {					
			$eol= $this->_EOL;
			$htaccess = "AuthType Basic".$eol;
			$htaccess.= "AuthName \"Access-logs\"".$eol;
			$htaccess.= "AuthUserFile ".$this->full_logs_path.".htpasswd".$eol;
			$htaccess.= "Require valid-user".$eol;
			$saveLocation= $this->full_logs_path.".htaccess";
			$ok= FileLogWriterHelper::writeToFile($saveLocation, $htaccess);
			if (!$ok) array_push($this->error, "write .htaccess failed.");
			$htpasswd= $this->loginUser.":".$this->loginPwHash;
			$saveLocation= $this->full_logs_path.".htpasswd";
			$ok= FileLogWriterHelper::writeToFile($saveLocation, $htpasswd);
			if (!$ok) array_push($this->error, "write .htpasswd failed.");
		}		
	}
	
	/** 
	  * Create logs directory.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  */
	protected function prepareLogDirectory() {
		if (!file_exists($this->full_logs_path)) {
			mkdir($this->full_logs_path);
		}
	}
	
	/** 
	  * Load configuration default values.
	  *
	  * @param string $key Config. entry name.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.1
	  */
	protected function setIniKeyDefault($key) {		
		switch (strtolower($key)) {
			case "eol_sequence":
				$this->eolSequence= "";
			break;
			case "eol_method":
				$this->eolMethod= "auto";
			break;
			case "logs_path":
				$this->logsPath= "logs";
			break;
			case "base_name":
				$this->baseName= "access_log";
			break;
			case "rotation_policy":
				$this->rotationPolicy= "daily";
				$this->timestampFormat= "Y-m-d";
			break;
			case "htaccess_protection":
				$this->htaccessProtection= "on";
			break;
		}
	}

	/** 
	  * Parse configuration values. 
	  * Set default value for key if given value is null.
	  *
	  * @param string $key Config. entry name.
	  * @param string $value Value to validate..
	  * @return array Configuration errors.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.3
	  */
	protected function validateIniKey($key, $value= null) {
		$err= null;
		
		$this->setIniKeyDefault(strtolower($key));
		if ($value) switch($key) {
			case "eol_sequence":
				$this->eolSequence= $value;
			break;
			case "eol_method":
				if (in_array(strtolower($value), ["auto", "windows", "win", "unix", "linux", "custom"])) {
					$this->eolMethod= strtolower($value);
					switch($this->eolMethod) {
						case "auto":
							$this->_EOL= FileLogWriterHelper::getOsEol();
						break;
						case "windows":
						case "win":
							$this->_EOL= FileLogWriterHelper::EOL_WIN;
						break;
						case "unix":
						case "linux":
							$this->_EOL= FileLogWriterHelper::EOL_UNIX;
						break;
						case "custom":
							$this->_EOL= $this->eolSequence;
						break;
					}		
				} else array_push($this->warning, "setting 'eol_method' is invalid (".$value."), using default");
			break;
			case "user":
				if ($value!="") {
					$this->loginUser= $value;
				} else {
					array_push($this->error, "no user set");
					$err= -1;
				}
			break;
			case "password":
				if ($value!="") {
					if (strlen($value)>=self::PW_STRENGTH) {
						$this->loginPwHash= FileLogWriterHelper::cryptApr1Md5($value);
					} else {
						array_push($this->error, "password too weak (min. 8 chars)");
						$err= -1;
					}
				} else {
					array_push($this->error, "no password set");
					$err= -1;
				}
			break;
			case "logs_path":
				if ($value!="") {
					$path= FileLogWriterHelper::FOLDER_SEPARATOR.FileLogWriterHelper::sanitizePath($value);
					if (!FileLogWriterHelper::pathLeavesOrEqualsRoot($path, $this->document_root)) {
						$this->logsPath= $path;
						$this->full_logs_path= FileLogWriterHelper::sanitizePath($this->document_root.$this->logsPath);
					} else array_push($this->warning, "setting 'logs_path' violates webserver document root, ".$value);
				} else array_push($this->warning, "setting 'logs_path' is empty, using default");
			break;
			case "base_name":
				if ($value!="") {
					$this->baseName= FileLogWriterHelper::sanitizeFilename($value);					
				} else array_push($this->warning, "setting 'base_name' is empty, using default");
			break;
			case "rotation_policy":
				if (in_array(strtolower($value), ["hourly", "daily", "weekly", "monthly", "yearly", "annual", "h", "d", "w", "m", "a", "y"])) {
					$this->rotationPolicy= strtolower($value);
					switch($this->rotationPolicy) {
						case "hourly":
						case "h":
							$this->timestampFormat= "Y-m-d-H";
						break;
						case "daily":
						case "d":
							$this->timestampFormat= "Y-m-d";
						break;
						case "weekly":
						case "w":
							$this->timestampFormat= "Y-W";
						break;
						case "monthly":
						case "m":
							$this->timestampFormat= "Y-m";
						break;
						case "annual":
						case "a":
						case "yearly":
						case "y":
							$this->timestampFormat= "Y";
						break;
					}		
				} else array_push($this->warning, "setting 'rotation_policy' is invalid (".$value."), using default");
			break;
			case "htaccess_protection":
				if (in_array(strtolower($value), ["on", "off"])) {
					$this->htaccessProtection= strtolower($value);
				} else array_push($this->warning, "setting 'htaccess_protection' is invalid (".$value."), using default");
			break;
		}
		
		return $err;
	}
			
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	protected function getLogTimestamp() {
		return $this->log_timestamp; 
	}

}

?>