<?php

/**
 * GetNewMarketplaceOrdersResponse.
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class GetNewMarketplaceOrdersResponse extends Response {
	/**
	 * are more orders available, than could be returned in this call
	 * @var type boolean
	 */
	public $moreAvailable;

	/**
	 * array of new orders, can be empty
	 * @var type CPOrder[]
	 */
	public $orders  = array();
}

?>
