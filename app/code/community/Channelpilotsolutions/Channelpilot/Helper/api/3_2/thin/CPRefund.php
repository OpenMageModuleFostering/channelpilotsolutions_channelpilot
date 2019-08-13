<?php
/**
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 3.2
 */
class CPRefund {
	/**The header of the order required to identify your order.
	 * @var type CPOrderHeader
	 */
	public $orderHeader;
    
	/**
	 * @var type string
	 */
	public $refundTime;
	
	/**
	 * @var type CPMoney
	 */
    public $refund;
	
	/**
	 * @var type string
	 */
    public $refundReason;
	
	function __construct($orderId, $refundTime, $refundReason, $net, $gross, $tax, $taxRate) {
		$this->orderHeader = new CPOrderHeader(null, $orderId, null, null, null, null);
		$this->refundTime = $refundTime;
		$this->refundReason = $refundReason;
		$this->refund = new CPMoney();
			$this->refund ->net = $net;
			$this->refund ->gross = $gross;
			$this->refund ->tax = $tax;
			$this->refund ->taxRate = $taxRate;
	}
}

?>