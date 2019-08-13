<?php

class Channelpilotsolutions_Channelpilot_Model_Payment extends Mage_Payment_Model_Method_Abstract {

	/**
	 * unique internal payment method identifier
	 *
	 * @var string [a-z0-9_]
	 */
	protected $_code = 'cp_mp';

	/**
	 * Is this payment method a gateway (online auth/charge) ?
	 */
	protected $_isGateway = false;

	/**
	 * Can authorize online?
	 */
	protected $_canAuthorize = true;

	/**
	 * Can capture funds online?
	 */
	protected $_canCapture = true;

	/**
	 * Can capture partial amounts online?
	 */
	protected $_canCapturePartial = false;

	/**
	 * Can refund online?
	 */
	protected $_canRefund = false;

	/**
	 * Can void transactions online?
	 */
	protected $_canVoid = true;

	/**
	 * Can use this payment method in administration panel?
	 */
	protected $_canUseInternal = true;

	/**
	 * Can show this payment method as an option on checkout payment page?
	 */
	protected $_canUseCheckout = false;

	/**
	 * Is this payment method suitable for multi-shipping checkout?
	 */
	protected $_canUseForMultishipping = true;

	/**
	 * Can save credit card information for future processing?
	 */
	protected $_canSaveCc = false;

	public function getCode() {
		return $this->_code;
	}

	public function getCcTypes() {
		$types = array();
		$types["cp_mp_default"] = "ChannelPilot Marketplace Payment Default";
		$types["cp_mp_amazon"] = "ChannelPilot Marketplace Payment Amazon";
		$types["cp_mp_ebay"] = "ChannelPilot Marketplace Payment Ebay";
//		$types["cp_mp_rakuten"] = "ChannelPilot Marketplace Payment Rakuten";
//		$types["cp_mp_cdiscount"] = "ChannelPilot Marketplace Payment CDiscount";
		return $types;
	}

	protected $_formBlockType = 'payment/form_cc';
	protected $_infoBlockType = 'payment/info_cc';

	public function assignData($data) {
		if (!($data instanceof Varien_Object)) {
			$data = new Varien_Object($data);
		}
		$info = $this->getInfoInstance();
		$tmp = $this->getCCTypes();
		$info->setCcType($tmp[$data->getCcType()]);
		return $this;
	}

	public function prepareSave() {
		$info = $this->getInfoInstance();
		$info->setCcNumber(null)
				->setCcCid(null);
		return $this;
	}

	public function validate() {
		parent::validate();

		$info = $this->getInfoInstance();
		$tmp = $this->getCCTypes();
		$activeTypes = explode(',', Mage::getStoreConfig('payment/' . $this->_code . '/types', Mage::app()->getStore()->getStoreId()));
		foreach ($activeTypes as $value) {
			if ($tmp[$value] === $info->getCcType()) {
				return $this;
			}
		}
		Mage::throwException('unknown type for ' . $this->_code);
	}

}

?>