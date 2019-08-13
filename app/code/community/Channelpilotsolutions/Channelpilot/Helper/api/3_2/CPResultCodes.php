<?php
/**
 * CPResultCodes. Collection of possible resultCodes for a request.
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 3.2
 */
class CPResultCodes {
	// successfull
	const SUCCESS				= 200;
	
	// client errors
	const AUTH_ERROR			= 401;
	const ID_NOT_FOUND			= 404;
	const METHOD_INVALID 		= 405;
	const REQUEST_INVALID 		= 406;
	const DUPLICATED_ID_FOUND	= 407; 
	const TOO_MANY_ELEMENTS		= 413;
	
	// server error
	const SYSTEM_ERROR			= 500;
	
	const SHOP_ERROR_PAYMENT_METHOD_UNKNOWN			= 700;
	const SHOP_ERROR_DELIVERY_METHOD_UNKNOWN		= 701;
	const SHOP_ERROR_ARTICLE_UNKNOWN				= 702;
	const SHOP_ERROR_ARTICLE_UNKNOWN_EXISTING_ORDER	= 703;
	const SHOP_ERROR_MORE_THAN_ONE_ARTICLES_FOUND	= 704;
}

?>
