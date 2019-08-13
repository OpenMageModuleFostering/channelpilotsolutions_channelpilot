<?php

/**
 * GetManagedArticlePricesResponse.
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 3.2
 */
class GetManagedArticlePricesResponse extends Response {
	/**
	 * array of managed article prices, can be empty
	 * @var type CPManagedArticlePrice[]
	 */
	
	public $moreAvailable;
	public $countMoreAvailable;
	public $managedArticlePrices  = array();
}

?>
