<?php

/**
 * The order class holds information about an order.
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPOrder {
	public $orderHeader;
	public $customer;
	
	public $addressInvoice;
	public $addressDelivery;
	
	public $itemsOrdered = array();
	
	public $shipping;
	public $payment;
	public $discount;
	
	public $summary;
}

?>
