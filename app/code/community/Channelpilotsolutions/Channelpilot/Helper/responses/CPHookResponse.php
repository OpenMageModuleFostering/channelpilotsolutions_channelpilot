<?php

/**
 * an cp hook response
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPHookResponse {

	public $resultCode;
	public $resultMessage;
	public $signaturePlugin;
	public $signatureShop;
	public $signaturePhp;
	public $moreAvailable;
	public $apiResultCode;
	public static $dbConnection;

	const shopsystem = 'Magento_';

	function __construct() {
		Mage::app('admin');

		$this->signaturePlugin = self::getModuleVersion();
		$this->signatureShop = self::getSignatureShop();
		$this->signaturePhp = urlencode(phpversion());
	}

	public static function getSignatureShop() {
		$signature = 'Magento_';
		$mage = new Mage();
		if(method_exists($mage,'getEdition')){
			$signature .= Mage::getEdition() . '_';
		}
		if(method_exists($mage,'getVersion')){
			$signature .= Mage::getVersion();
		}
		return $signature;
	}

	public static function getModuleVersion(){
        $version = (string)Mage::getConfig()->getNode('modules/Channelpilotsolutions_Channelpilot/version');
		return self::shopsystem . $version;
	}

	public function writeResponse($header, $response) {
		header($header);
		print_r($response);
        exit();
	}

}

?>
