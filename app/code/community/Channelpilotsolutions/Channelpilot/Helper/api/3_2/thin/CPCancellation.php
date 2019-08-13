<?php

/**
 * Holds information about a cancellation.
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 3.2
 */
class CPCancellation {
	
	/**The header of the order required to identify your order.
	 * @var type CPOrderHeader
	 */
	public $orderHeader;
	
	/**
	 * @var type string
	 */
	public $cancellationTime;
	
	/**
	 * @var type boolean
	 */
	public $isWholeOrderCancelled;
	
	/**
	 * @var type CPOrderItem[]
	 */
	public $cancelledItems = array();
	
	function __construct($orderId, $statusIdBefore, $source, $cancellationTime, $isWholeOrderCancelled) {
		$this->orderHeader = new CPOrderHeader (null,$orderId,$source, $isWholeOrderCancelled ? CPOrderStatus::ID_CANCELLED : $statusIdBefore, false, null);
		$this->cancellationTime = $cancellationTime;
		$this->isWholeOrderCancelled = $isWholeOrderCancelled;
	}
}

?>
