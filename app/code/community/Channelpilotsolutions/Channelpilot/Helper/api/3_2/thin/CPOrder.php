<?php

/**
 * The order class holds information about an order.
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 3.2
 */
class CPOrder {
	
	/** 
	 * @var type CPOrderHeader
	 */
	public $orderHeader;
	
	/** 
	 * @var type CPCustomer
	 */
	public $customer;
	
	/** 
	 * @var type CPAdress
	 */
	public $addressInvoice;
	
	/** 
	 * @var type CPAdress
	 */
	public $addressDelivery;
	
	/** 
	 * @var type CPOrderItem[]
	 */
	public $itemsOrdered = array();
	
	/** 
	 * @var type shipping
	 */
	public $shipping;
	
	/** 
	 * @var type CPPayment
	 */
	public $payment;
	
	/** 
	 * @var type CPOrderSummary
	 */
	public $summary;
	
	/**
	 * @var type CPMoney
	 */
	public $discount;
	
	/**
	 * @var type string
	 */
	public $expectedShippingTimeFrom;
	
	/**
	 * @var type string
	 */
	public $expectedShippingTimeTo;
}

?>
