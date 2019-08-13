<?php

/**
 * an order status
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPOrderStatus {
	const ID_READY_FOR_EXPORT		= "10";
	const ID_IMPORTED				= "20";
	const ID_PARTIALLY_DELIVERED	= "25";
	const ID_DELIVERED				= "30";
	const ID_CANCELLED				= "99";
	
	/**
	 * the unique-id for this orderStatus. Has a value of the constants defined in this class.
	 * @var type string
	 */
	public $identifier;
	
	/**
	 * the public title for the status, can be null
	 * @var type string
	 */
	public $publicTitle;
	
	/**
	 * the public description for the status, can be null
	 * @var type string
	 */
	public $publicDescription;
	
	/**
	 * was the orderimport successfully
	 * @var type boolean
	 */
	public $hasError;
	
	/**
	 * the public errormessage, can be null
	 * @var type string
	 */
	public $errorMessage;
	
	/**
	 * which error, can be null
	 * @var type int
	 */
	public $errorCode;
	
	function __construct($identifier, $hasError, $errorCode) {
		$this->identifier = $identifier;
		$this->hasError = $hasError;
		$this->errorCode = $errorCode;
	}
}

?>
