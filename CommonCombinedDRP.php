<?php
namespace phpWTL;

/**
  * Helper class for data retrieval policy constants for the common and combined log format
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.0
  * @api All constant names (actual values might be subject to change)
  */
class CommonCombinedDRP {
	/** Policy for the retrieval of the content-length. */
	const DRP_CC_CONTENT_LENGTH_RETRIEVAL = 100;
	/** Content-length retrieval: Measure the size of the php script (default). */
	const DRP_CC_CLR_SCRIPT = 101;
	/** Content-length retrieval: Measure the size of the php output buffer (approximation). */
	const DRP_CC_CLR_BUFFER = 102;
	/** Content-length retrieval: Measure the size of an individual file. The filename/path is set via the "parameter" attribute of the DataRetrievalPolicy object. */
	const DRP_CC_CLR_CUSTOM = 103;
}
?>