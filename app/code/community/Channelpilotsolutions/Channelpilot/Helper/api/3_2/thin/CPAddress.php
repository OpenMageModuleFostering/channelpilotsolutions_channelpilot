<?php

/**
 * Holds an address. e.g. a shipping-address
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 3.2
 */
class CPAddress {
	
	/**
	 * 1 for male, 2 for female, -1 for unknown/default. ChannelPilot will try to determine genderId if not provided by the channel.
	 * @var type int
	 */
	public $genderId;
	
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
	 * @var type string
	 */
	public $company;
	
	/**
	 * @var type string
	 */
	public $streetTitle;
	
	/**
	 * @var type string
	 */
	public $streetNumber;
	
	/**
	 * @var type string
	 */
	public $streetFull;
	
	/**
	 * @var type string
	 */
	public $streetExtra;
	
	/**
	 * @var type string
	 */
	public $zip;
	
	/**
	 * @var type string
	 */
	public $city;
	
	/**
	 * @var type string
	 */
	public $countryIso2;
	
	/**
	 * @var type string
	 */
	public $countryIso3;
	
	/**
	 * @var type string
	 */
	public $state;
	
	/**
	 * @var type string
	 */
	public $phone;
}

?>
