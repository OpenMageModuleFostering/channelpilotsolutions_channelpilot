<?php

/**
 * Holds information about a cancellation.
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPCancellation {
	public $orderHeader;
	public $isWholeOrderCancelled;
	public $cancellationTime;
	public $cancelledItems = array();
	
	function __construct($orderId, $source, $statusIdBefore, $cancellationTime, $isWholeOrderCancelled) {
		$this->orderHeader = new CPOrderHeader(null, $orderId, $source, $isWholeOrderCancelled ? CPOrderStatus::ID_CANCELLED : $statusIdBefore, false, null);
		$this->cancellationTime = $cancellationTime;
		$this->isWholeOrderCancelled = $isWholeOrderCancelled;
	}
}

?>
