<?php

/**
 * Holds information about a customer.
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 3.2
 */
class CPCustomer {
	
	/**
	 * ID of the customer on the marketplace.
	 * @var type string
	 */
	public $idExternal;
	
	/**
	 * 0 for male, 1 for female. ChannelPilot will try to determine genderId if not provided by
	 * @var type int
	 */
	public $genderId;
	
	/** 
	 * @var type CPCustomerGroup[]
	 */
	public $customerGroups = array();
	
	/** 
	 * @var type string
	 */
	public $nameFirst;
	
	/** 
	 * @var type string
	 */
	public $nameLast;
	
	/** 
	 * @var type string
	 */
	public $nameFull;
	
	/**
	 * Email of the customer.
	 * Some marketplaces provide “forward-mails” that are only valid for
	 * email-forwarding through the marketplace
	 * and not for direct communication (e.g.Amazon).
	 * @var type string
	 */
	public $email;
	
	/** 
	 * @var type string
	 */
	public $phone;
	
	/** 
	 * @var type string
	 */
	public $mobile;
	
	/** 
	 * @var type string
	 */
	public $fax;
}

?>
