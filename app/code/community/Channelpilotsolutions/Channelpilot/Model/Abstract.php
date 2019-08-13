<?php

class Channelpilotsolutions_Channelpilot_Model_Abstract extends Mage_Payment_Model_Method_Abstract {

	protected $_formBlockType = 'payment/form_cc';
	protected $_infoBlockType = 'payment/info_cc';
	protected $_canSaveCc = false;

	public function assignData($data) {
		if (!($data instanceof Varien_Object)) {
			$data = new Varien_Object($data);
		}
		$info = $this->getInfoInstance();
		$info->setCcType($data->getCcType())
				->setCcOwner($data->getCcOwner())
				->setCcLast4(substr($data->getCcNumber(), -4))
				->setCcNumber($data->getCcNumber())
				->setCcCid($data->getCcCid())
				->setCcExpMonth($data->getCcExpMonth())
				->setCcExpYear($data->getCcExpYear())
				->setCcSsIssue($data->getCcSsIssue())
				->setCcSsStartMonth($data->getCcSsStartMonth())
				->setCcSsStartYear($data->getCcSsStartYear())
		;
		return $this;
	}

	public function prepareSave() {
		$info = $this->getInfoInstance();
		if ($this->_canSaveCc) {
			$info->setCcNumberEnc($info->encrypt($info->getCcNumber()));
		}
		//$info->setCcCidEnc($info->encrypt($info->getCcCid()));
		$info->setCcNumber(null)
				->setCcCid(null);
		return $this;
	}

	public function validate() {
		$info = $this->getInfoInstance();
		$errorMsg = false;
		$availableTypes = explode(',', $this->getConfigData('cctypes'));

		$ccNumber = $info->getCcNumber();

		// remove credit card number delimiters such as "-" and space
		$ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
		$info->setCcNumber($ccNumber);

		$ccType = '';
		$specifiedCCType = $info->getCcType();

		return $this;
	}

	public function hasVerification() {
		Mage::log("hasVerification\n", null, 'cp_plugincc.log');
		$configData = $this->getConfigData('useccv');
		if (is_null($configData)) {
			return true;
		}
		return (bool) $configData;
	}

	public function getVerificationRegEx() {
		Mage::log("getVerificationRegEx\n", null, 'cp_plugincc.log');
		$verificationExpList = array(
			'VI' => '/^[0-9]{3}$/', // Visa
			'MC' => '/^[0-9]{3}$/', // Master Card
			'AE' => '/^[0-9]{4}$/', // American Express
			'DI' => '/^[0-9]{3}$/', // Discovery
			'SS' => '/^[0-9]{3,4}$/',
			'SM' => '/^[0-9]{3,4}$/', // Switch or Maestro
			'SO' => '/^[0-9]{3,4}$/', // Solo
			'OT' => '/^[0-9]{3,4}$/',
			'JCB' => '/^[0-9]{3,4}$/' //JCB
		);
		return $verificationExpList;
	}

	public function validateCcNum($ccNumber) {


		$cardNumber = strrev($ccNumber);
		$numSum = 0;

		for ($i = 0; $i < strlen($cardNumber); $i++) {
			$currentNum = substr($cardNumber, $i, 1);

			/**
			 * Double every second digit
			 */
			if ($i % 2 == 1) {
				$currentNum *= 2;
			}

			/**
			 * Add digits of 2-digit numbers together
			 */
			if ($currentNum > 9) {
				$firstNum = $currentNum % 10;
				$secondNum = ($currentNum - $firstNum) / 10;
				$currentNum = $firstNum + $secondNum;
			}

			$numSum += $currentNum;
		}

		/**
		 * If the total has no remainder it's OK
		 */
		Mage::log("validateCcNum: " . ($numSum % 10 == 0) . "\n", null, 'cp_plugincc.log');
		return ($numSum % 10 == 0);
	}

	public function validateCcNumOther($ccNumber) {

		return preg_match('/^\\d+$/', $ccNumber);
	}

	public function isAvailable($quote = null) {
		Mage::log($this->getConfigData('cctypes', ($quote ? $quote->getStoreId() : null)) && parent::isAvailable($quote) . " isAvailable\n", null, 'cp_plugincc.log');
		return $this->getConfigData('cctypes', ($quote ? $quote->getStoreId() : null)) && parent::isAvailable($quote);
	}

	public function getIsCentinelValidationEnabled() {
		return false !== Mage::getConfig()->getNode('modules/Mage_Centinel') && 1 == $this->getConfigData('centinel');
	}

	public function getCentinelValidator() {
		$validator = Mage::getSingleton('centinel/service');
		$validator
				->setIsModeStrict($this->getConfigData('centinel_is_mode_strict'))
				->setCustomApiEndpointUrl($this->getConfigData('centinel_api_url'))
				->setStore($this->getStore())
				->setIsPlaceOrder($this->_isPlaceOrder());
		return $validator;
	}

	public function getCentinelValidationData() {
		$info = $this->getInfoInstance();
		$params = new Varien_Object();
		$params
				->setPaymentMethodCode($this->getCode())
				->setCardType($info->getCcType())
				->setCardNumber($info->getCcNumber())
				->setCardExpMonth($info->getCcExpMonth())
				->setCardExpYear($info->getCcExpYear())
				->setAmount($this->_getAmount())
				->setCurrencyCode($this->_getCurrencyCode())
				->setOrderNumber($this->_getOrderId());
		return $params;
	}

    protected function _getOrderId() {
		$info = $this->getInfoInstance();

		if ($this->_isPlaceOrder()) {
			return $info->getOrder()->getIncrementId();
		} else {
			if (!$info->getQuote()->getReservedOrderId()) {
				$info->getQuote()->reserveOrderId();
			}
			return $info->getQuote()->getReservedOrderId();
		}
	}

    protected function _getAmount() {
		$info = $this->getInfoInstance();
		if ($this->_isPlaceOrder()) {
			return (double) $info->getOrder()->getQuoteBaseGrandTotal();
		} else {
			return (double) $info->getQuote()->getBaseGrandTotal();
		}
	}

    protected function _getCurrencyCode() {
		$info = $this->getInfoInstance();

		if ($this->_isPlaceOrder()) {
			return $info->getOrder()->getBaseCurrencyCode();
		} else {
			return $info->getQuote()->getBaseCurrencyCode();
		}
	}

    protected function _isPlaceOrder() {
		$info = $this->getInfoInstance();
		if ($info instanceof Mage_Sales_Model_Quote_Payment) {
			return false;
		} elseif ($info instanceof Mage_Sales_Model_Order_Payment) {
			return true;
		}
	}

	public function getInfoInstance() {
		$instance = Mage::getSingleton('channelpilot/payment');
		return $instance;
	}

}
