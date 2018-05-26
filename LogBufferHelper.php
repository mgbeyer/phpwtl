<?php
namespace phpWTL;

/**
  * Helper class for log buffer
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.2
  * @api All constant names (actual values might be subject to change)
  */
class LogBufferHelper {
	/** Disable buffering (buffer becomes a mere wrapper) */
	const BUFFER_OFF= 0;
	/** Disable auto flush feature */
	const BUFFER_INFINITE= PHP_INT_MAX;
	/** Default buffer size */
	const BUFFER_SIZE_DEFAULT= 32;
	/** Callback: Flush iteration (content objects) */
	const CALLBACK_FLUSH_EACH= "cb_flush_each";
	/** Callback: Log method, before (logger object) */
	const CALLBACK_LOG_BEFORE= "cb_log_before";
	/** Callback: Log method, after (logger object) */
	const CALLBACK_LOG_AFTER= "cb_log_after";
}
?>