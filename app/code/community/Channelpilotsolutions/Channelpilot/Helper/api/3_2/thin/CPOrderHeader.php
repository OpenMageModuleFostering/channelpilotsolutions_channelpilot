<?php

/**
 * meta-data for an order.
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 3.2
 */
class CPOrderHeader {
	
	/** 
	 * @var type string
	 */
	public $orderId;
	
	/** 
	 * @var type string
	 */
	public $orderIdExternal;
	
	/** 
	 * @var type string
	 */
	public $orderIdExternalTransactionId;
	
	/** 
	 * @var type string
	 */
	public $source;
	
	/** 
	 * @var type CPOrderStatus
	 */
	public $status; 
	
	/** 
	 * @var type string
	 */
	public $orderTime;
	
	/** 
	 * @var type string
	 */
	public $purchaseOrderNumber;
	
	/** 
	 * @var type boolean
	 */
	public $isBusinessOrder;
	
	function __construct($orderIdExternal, $orderId, $source, $statusIdentifier, $hasError, $errorCode) {
		$this->orderIdExternal = $orderIdExternal;
		$this->orderId = $orderId;
		$this->source = $source;
		$this->status = new CPOrderStatus($statusIdentifier, $hasError, $errorCode);
	}
}

?>
