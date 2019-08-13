<?php

/**
 * GetNewMarketplaceOrdersResponse.
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 3.2
 */
class GetNewMarketplaceOrdersResponse extends Response {
	/**
	 * are more orders available, than could be returned in this call
	 * @var type boolean
	 */
	public $moreAvailable;

	/**
	 *
	 */
	public $countMoreAvailable;
	
	/**
	 * array of new orders, can be empty
	 * @var type CPOrder[]
	 */
	public $orders  = array();
}

?>
