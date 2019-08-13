<?php

/**
 * Holds information about a delivery.
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPDelivery {
	/**
	 * @var type CPOrderHeader
	 */
	public $orderHeader;

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
	 * the Carrier (DHL/UPS/...). if not set, channelpilot will take the default-Carrier defined for the orders shippingType
	 * @var type String
	 */
	public $carrierName;

	/**
	 *
	 * @var type CPShipping
	 */
	public $shipping;

	public $deliveryTime;

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
