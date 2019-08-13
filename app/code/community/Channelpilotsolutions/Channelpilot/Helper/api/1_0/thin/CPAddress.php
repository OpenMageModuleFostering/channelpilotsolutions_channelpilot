<?php

/**
 * Holds an address. e.g. a shipping-address
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPAddress {
	public $nameFirst;
	public $nameLast;
	public $nameFull;
	
	public $company;
	
	public $streetTitle;
	public $streetNumber;
	public $streetFull;
	public $streetExtra;
	
	public $city;
	public $state;
	public $zip;
	
	public $countryIso2;
	public $countryIso3;
	
	public $phone;
}

?>
