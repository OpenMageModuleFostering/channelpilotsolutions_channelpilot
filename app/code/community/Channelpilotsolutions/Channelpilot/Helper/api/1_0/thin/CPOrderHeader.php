<?php

/**
 * meta-data for an order.
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPOrderHeader {
	public $orderId;
	public $orderIdExternal;
	
	public $status;
	
	public $source;
	
	public $orderTime;
	
	function __construct($orderIdExternal, $orderId, $source, $statusIdentifier, $hasError, $errorCode) {
		$this->orderIdExternal = $orderIdExternal;
		$this->orderId = $orderId;
		$this->source = $source;
		$this->status = new CPOrderStatus($statusIdentifier, $hasError, $errorCode);
	}
}

?>
