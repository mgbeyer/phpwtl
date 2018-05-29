<?php
namespace phpWTL\Tools;
use phpWTL\LogBuffer;
use phpWTL\LogBufferHelper;
use phpWTL\DataRetrievalPolicy;
use phpWTL\DataRetrievalPolicyHelper;
use phpWTL\DRP;
use phpWTL\SALTHelper;

require_once __DIR__ .'/../LogBuffer.php';
require_once __DIR__ .'/../LogBufferHelper.php';
require_once __DIR__ .'/../DataRetrievalPolicy.php';
require_once __DIR__ .'/../DataRetrievalPolicyHelper.php';
require_once __DIR__ .'/../DRP.php';
require_once 'SALTHelper.php';

// simple callback for LogBuffer flush loop
function simpleFlush($writer_object, $content_object) {
	$writer_object->writeToLog($content_object);
}

/**
  * S.A.L.T. - [S]tatic [A]sset [L]ogging [T]ool
  * Allows for proper (webserver like) logging sequence of static assets included in a page
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.0
  * @api
  */
class SALT {
	/** LogBuffer */
	protected $_logbuffer= null;

	/**
	  * @param object $logger Provide the logger to use. 
	  * @param object $writer Provide the log writer to use. 
	  * @param array $buffercallbacks Provide array of callback functions for LogBuffer. 
	  * @param array $bufferparams Provide parameters for the internal LogBuffer. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
   public function __construct($logger, $writer, $buffercallbacks= null, $bufferparams= null) {
		if ($logger && $writer) {
			if ($buffercallbacks==null) {
				$buffercallbacks= array (
					LogBufferHelper::CALLBACK_FLUSH_EACH => "phpWTL\Tools\simpleFlush"
				);
			}
			if ($bufferparams==null) {
				$myBufferParams= array ("buffer_size" => LogBufferHelper::BUFFER_INFINITE);
			} else {
				$bufferparams["buffer_size"]= LogBufferHelper::BUFFER_INFINITE;
			}
			$this->_logbuffer= new LogBuffer($logger, $writer, $buffercallbacks, $bufferparams);
		}
   }

	/**
	  * Log asset
	  *
	  * @param string $src Path and filename of resource.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	private function logAsset($src) {
		$pol= array(
			new DataRetrievalPolicy(
				array(
					'name' => DRP::DRP_CONTENT_LENGTH_RETRIEVAL, 
					'flag' => DRP::DRP_CLR_CUSTOM,
					'parameter' => $src
				)
			)
		);
		$oldpol= $this->_logbuffer->getLogger()->getDataRetrievalPolicies();
		$this->_logbuffer->getLogger()->setDataRetrievalPolicies($pol);
		$this->_logbuffer->log();
		$this->_logbuffer->getLogger()->setDataRetrievalPolicies($oldpol);
	}

	/**
	  * Log asset of preset type to buffer and write corr. tag
	  *
	  * @param int $type Type of resource to log (see SALTHelper for constant def.).
	  * @param string $src Path and filename of resource.
	  * @param array $attr Assoc. array of key-value-pairs of attributes for a tag (optional, * = set $src as value).
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function logAssetType($type, $src, $attr= null) {
		switch($type) {
			case SALTHelper::SALT_RES_IMG:
				echo includeImg($src, $attr);
			break;
			case SALTHelper::SALT_RES_CSS:
				echo includeCss($src, $attr);
			break;
			case SALTHelper::SALT_RES_JS:
				echo includeJs($src, $attr);
			break;
			case SALTHelper::SALT_RES_IFRAME:
				echo includeIframe($src, $attr);
			break;
			case SALTHelper::SALT_RES_EMBED:
				echo includeEmbed($src, $attr);
			break;
		}
		$this->logAsset($src);
	}

	/**
	  * Log asset of custom type to buffer and write corr. tag
	  *
	  * @param string $name Name of tag to create.
	  * @param string $src Path and filename of resource.
	  * @param array $attr Assoc. array of key-value-pairs of attributes for a tag (optional, * = set $src as value).
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function logAssetCustom($name, $src, $attr= null) {
		echo buildTag($name, $src, $attr);
		$this->logAsset($src);
	}

	/**
	  * Flush logbuffer to writer
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function finalize() {
		$this->_logbuffer->flush();
	}

	/**
	  * @return object Get the logbuffer object. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
    public function getLogbuffer() {
		if (!(null === $this->_logbuffer)) return $this->_logbuffer;
	}


	/**
	  * Construct an arbitrary HTML tag
	  *
	  * @param string $name Name of tag to create.
	  * @param string $src Path and filename of resource (optional).
	  * @param array $attr Assoc. array of key-value-pairs of attributes for the tag (optional, * = set $src as value).
	  * @return String containing tag created
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	static public function buildTag($name, $src= null, $attr= null) {
		$attrib= "";
		if ($attr) foreach ($attr as $k => $v) {
			if ($v=="*") $v= $src;
			$attrib= $attrib." ".$k."='".$v."'";
		}
		return "<".$name.$attrib."/>";
	}

	/**
	  * Wrapper to create img tag.
	  *
	  * @param string $src Path and filename of resource (optional).
	  * @param array $attr Assoc. array of key-value-pairs of additional attributes for a tag (optional, * = set $src as value).
	  * @return String containing tag created
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	static public function includeImg($src, $attr= null) {
		return buildTag("img", $src, array_merge($attr, array('src' => '*')));
	}

	/**
	  * Wrapper to inlude CSS stylesheet.
	  *
	  * @param string $src Path and filename of resource (optional).
	  * @param array $attr Assoc. array of key-value-pairs of additional attributes for a tag (optional, * = set $src as value).
	  * @return String containing tag created
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	static public function includeCss($src, $attr= null) {
		return buildTag("link", $src, array_merge($attr, array(
			'rel' => 'stylesheet', 
			'type' => 'text/css',
			'href' => '*'
		)));
	}

	/**
	  * Wrapper to inlude JS script file.
	  *
	  * @param string $src Path and filename of resource (optional).
	  * @param array $attr Assoc. array of key-value-pairs of additional attributes for a tag (optional, * = set $src as value).
	  * @return String containing tag created
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	static public function includeJs($src, $attr= null) {
		return buildTag("rel", $src, array_merge($attr, array(
			'type' => 'text/javascript',
			'src' => '*'
		)));
	}
	
	/**
	  * Wrapper to inlude iFrame.
	  *
	  * @param string $src Path and filename of resource (optional).
	  * @param array $attr Assoc. array of key-value-pairs of additional attributes for a tag (optional, * = set $src as value).
	  * @return String containing tag created
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	static public function includeIframe($src, $attr= null) {
		return buildTag("iframe", $src, array_merge($attr, array(
			'src' => '*'
		)));
	}

	/**
	  * Wrapper to inlude embed tag.
	  *
	  * @param string $src Path and filename of resource (optional).
	  * @param array $attr Assoc. array of key-value-pairs of additional attributes for a tag (optional, * = set $src as value).
	  * @return String containing tag created
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	static public function includeEmbed($src, $attr= null) {
		return buildTag("embed", $src, array_merge($attr, array(
			'src' => '*'
		)));
	}

}
?>
