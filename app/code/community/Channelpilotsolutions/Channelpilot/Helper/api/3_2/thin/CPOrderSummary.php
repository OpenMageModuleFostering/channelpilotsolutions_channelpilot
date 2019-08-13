<?php
/**
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 3.2
 */
class CPOrderSummary {
	
	/** 
	 * @var type string
	 */
	public $currencyIso3;
	
	/** 
	 * @var type CPMoney
	 */
	public $totalSumItems;
	
	/** 
	 * @var type CPMoney
	 */
	public $totalSumOrder;
	
	/** 
	 * @var type string
	 */
	public $message;
	
	/** 
	 * @var type Number
	 */
	public $feeTotalNet;
}

?>
