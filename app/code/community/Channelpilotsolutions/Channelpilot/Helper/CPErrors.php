<?php

/**
 * CPResultCodes. Collection of possible errors for a request.
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPErrors {
	// register
	const RESULT_SHOP_UNKNOWN = 600;
	const RESULT_MISSING_PARAMS = 601;
	const RESULT_ALREADY_REGISTERED = 602;

	// order
	const RESULT_NUMBER_COLUMN_UNKNOWN = 750;
	
	// common
	const RESULT_OK = 200;
	const RESULT_SIGNATURE_MISMATCH = 400;
	const RESULT_FAILED = 900;
	const RESULT_TIMEOUT = 901;
	const RESULT_CONFIG_INVALID = 902;
	const RESULT_API_DEACTIVATED = 903;

}

?>
