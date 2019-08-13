<?php

/**
 * Holds information about a delivery.
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 3.2
 */
class CPDelivery {
	
	/**The header of the order required to identify your order.
	 * @var type CPOrderHeader
	 */
	public $orderHeader;
	
	/** 
	 * @var type string
	 */
	public $deliveryTime;
	
	/**
	 * the carrier (DHL/UPS/...). if not set, channelpilot will take the default-carrier defined for the orders shippingType
	 * @var type String
	 */
	public $carrierName;
	
	/**
	 * are all to be delivered items deliverd after this delivery?
	 * @var type boolean
	 */
	public $isDeliveryCompleted;
	
	/**
	 * tracking-number for this delivery
	 * @var type String
	 */
	public $trackingNumber;
	
	/**
	 * 
	 * @var type CPShipping
	 */
	public $shipping;
	
	
	/**
	 * array of delivered items. is only evaluated and neccessary if ($isDeliveryCompleted == false).
	 * @var type CPOrderItem[]
	 */
	public $deliveredItems = array();
	
	function __construct($orderId, $source, $isDeliveryCompleted, $trackingNumber, $deliveryTime) {
		$this->orderHeader = new CPOrderHeader(null, $orderId, $source, $isDeliveryCompleted ? CPOrderStatus::ID_DELIVERED : CPOrderStatus::ID_PARTIALLY_DELIVERED, null, false);
		$this->trackingNumber = $trackingNumber;
		$this->deliveryTime = $deliveryTime;
		$this->isDeliveryCompleted = $isDeliveryCompleted;
	}
}

?>
