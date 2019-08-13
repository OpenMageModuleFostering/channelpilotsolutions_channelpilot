<?php

/**
 * an cp error handler
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPErrorHandler extends CPAbstractHandler {

	/**
	 * Handle error event.
	 * Output a short error message and log the complete error message.
	 * 
	 * @param type $code
	 * @param type $message
	 * @param type $logMessage
	 */
	public static function handle($code, $message, $logMessage) {
		self::logError($logMessage);
		$hook = new CPHookResponse();
		$hook->resultCode = $code;
		$hook->resultMessage = $message;
		$hook->writeResponse(self::defaultHeader, json_encode($hook));
	}
	
	
}
?>
