<?php

/**
 * special customer functions
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CustomerFunctions {

	public static function insertOrder($oOrder, $apiOrder) {
		return $oOrder;
	}

	public static function insertOrUpdateUser($oCustomer, $apiOrder) {
		$oCustomer->email = str_replace("@", "[at]", $apiOrder->customer->email);
		return $oCustomer;
	}

	public static function getUserName($userName) {
		return str_replace("@", "[at]", $userName);
	}

	public static function createAddress($oAddress, $apiOrder) {
		return $oAddress;
	}

}

?>
