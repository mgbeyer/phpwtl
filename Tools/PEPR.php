<?php
namespace phpWTL\Tools;
use phpWTL\PEPRHelper;
use phpWTL\LogBuffer;
use phpWTL\LogBufferHelper;
use phpWTL\DataRetrievalPolicy;
use phpWTL\DataRetrievalPolicyHelper;
use phpWTL\DRP;

require_once __DIR__ .'/../LogBuffer.php';
require_once __DIR__ .'/../LogBufferHelper.php';
require_once __DIR__ .'/../DataRetrievalPolicy.php';
require_once __DIR__ .'/../DataRetrievalPolicyHelper.php';
require_once __DIR__ .'/../DRP.php';
require_once 'PEPRHelper.php';

// simple callback for LogBuffer flush loop
function simpleFlush($writer_object, $content_object) {
	$writer_object->writeToLog($content_object);
}

/**
  * P.E.P.R. - [P]phWTL [E]xternal [P]lunder [R]aider
  * Tool for automatic logging of static assets included in a page, in proper (webserver like) sequence
  * (Needs the data retrieval policy "DRP_CONTENT_LENGTH_RETRIEVAL" set to "DRP_CLR_BUFFER" (enabled PHP output buffering) to work)
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.0
  * @api
  */
class PEPR {
	/** default name for tag resource mappings ini file. */
	const TAG_RESOURCE_MAPPINGS_DEFAULT_INI= "../config/PEPR-TagResourceMappings.ini";

	/** LogBuffer */
	protected $_logbuffer= null;
	/** Resource types description */
	protected $_resTypes= null;
	/** manual finalization */
	protected $_manualfinalize= null;

	/**
	  * @param object $logger Provide the logger to use. 
	  * @param object $writer Provide the log writer to use. 
	  * @param boolean $manual_finalize If set to "true" automatic finalization is disabled (enabled by default).
	  * @param array $restypes Description of different resource tags and their attribute(s) referencing the resource.
	  * @param array $buffercallbacks Provide array of callback functions for the internal LogBuffer. 
	  * @param array $bufferparams Provide parameters for LogBuffer. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
   public function __construct($logger, $writer, $restypes, $manual_finalize= false, $buffercallbacks= null, $bufferparams= null) {
		if ($logger && $writer && $restypes) {
			if ($buffercallbacks==null) {
				$buffercallbacks= array (
					LogBufferHelper::CALLBACK_FLUSH_EACH => "phpWTL\Tools\simpleFlush"
				);
			}
			if ($bufferparams==null) {
				$bufferparams= array ("buffer_size" => LogBufferHelper::BUFFER_INFINITE);
			} else {
				$bufferparams["buffer_size"]= LogBufferHelper::BUFFER_INFINITE;
			}
			$this->_resTypes= $restypes;
			$this->_manualfinalize= $manual_finalize;
			$this->_logbuffer= new LogBuffer($logger, $writer, $buffercallbacks, $bufferparams);
		}
   }

	/**
	  * Parse $html and log all assets found to the buffer
	  *
	  * @param string $html HTML to parse.
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public function logAssets($html) {
		$doc= new \DOMDocument();
		// relax some warnings
		$doc->recover= true;
		$doc->strictErrorChecking= false;
		libxml_use_internal_errors(true);
		$doc->loadHTML(utf8_encode($html));
		$xpath= new \DOMXpath($doc);
		$tagset= "";
		$i= 0;
		$numItems= count($this->_resTypes);
		// parse for all relevant tags
		foreach ($this->_resTypes as $k => $v) {
			$tagset= $tagset."//".$k." |";
			if(++$i < $numItems) $tagset= $tagset." ";
		}
		$tagsFound= $xpath->query($tagset);
		// build resource tag list
		$src= array();
		for ($i = 0; $i < $tagsFound->length; $i++) {
			$elem= $tagsFound->item($i);
			$attr= "";
			// check each potential resource attribute
			foreach ($this->_resTypes[$elem->nodeName]['resource'] as $k => $v) {
				if ($elem->hasAttribute($v)) {
					$attr= utf8_decode($elem->getAttribute($v));
					if ($attr!="") {
						// omit resources pointing to external URLs
						$attr_host= parse_url($attr, PHP_URL_HOST);
						if ($attr_host!="") {
							if ($attr_host!=$_SERVER['HTTP_HOST']) $attr= "";
						}
					}
				}
				if ($attr!="") break;
			}	
			if ($attr!="") $src[]= $attr;
		}
		
		$oldpol= $this->_logbuffer->getLogger()->getDataRetrievalPolicies();
		foreach ($src as $v) {
			$pol= array(
				new DataRetrievalPolicy(
					array(
						'name' => DRP::DRP_CONTENT_LENGTH_RETRIEVAL, 
						'flag' => DRP::DRP_CLR_CUSTOM,
						'parameter' => $v
					)
				)
			);
			$this->_logbuffer->getLogger()->setDataRetrievalPolicies($pol);
			$this->_logbuffer->log();
		}
		$this->_logbuffer->getLogger()->setDataRetrievalPolicies($oldpol);
		if (!$this->_manualfinalize) $this->finalize();
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
	
}
?>
