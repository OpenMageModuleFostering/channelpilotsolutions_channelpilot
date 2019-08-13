<?php
/**
 * Basic authentication class to use the ChannelPilot seller API.
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPAuth {
	/**
	 * MerchantId for your ChannelPilot account. You can get from go.channelpilot.com/api.
	 * @var type string
	 */
	public $merchantId;
	/**
	 * ShopToken for your shop in ChannelPilot. You can get from go.channelpilot.com/api.
	 * @var type string
	 */
	public $shopToken;
	
	function __construct($merchantId, $shopToken) {
		$this->merchantId = $merchantId;
		$this->shopToken = $shopToken;
	}
}
?>
