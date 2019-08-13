<?php

/**
 * an cp shop
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPShop {

	public $title;
	public $id;
	public $active;
	public $isRegistered;
	public $deliveryTypes;
	public $paymentTypes;

}

/**
 * an cp payment type
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPPaymentType {

	public $title;
	public $id;
	public $active;

}

/**
 * an cp customer group type
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPCustomerGroupType {

	public $title;
	public $id;
	public $active;

}

/**
 * an cp delivery type
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPDeliveryType {

	public $title;
	public $id;
	public $active;

}

/**
 * an cp status handler
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPStatusHandler extends CPAbstractHandler {

	/**
	 * Handle status event
	 */
	public function handle() {
		$hook = new CPGetStatusHookResponse();
		$hook->shops = self::getShops();
		$hook->resultCode = CPResultCodes::SUCCESS;
		$hook->resultMessage = "ok";
		$hook->writeResponse(self::defaultHeader, json_encode($hook));
	}

	private function getShops() {
		$allStores = Mage::app()->getStores();
		$shops = array();
		foreach ($allStores as $_eachStoreId => $val) {
			$shop = new CPShop();
			$shop->id = Mage::app()->getStore($_eachStoreId)->getId();
			$shop->title = Mage::app()->getStore($_eachStoreId)->getName();
			$shop->active = (bool) Mage::app()->getStore()->getIsActive();
			$shop->isRegistered = self::isShopRegistered($shop->id);
			$shop->deliveryTypes = self::getDeliveryTypes($shop->id);
			$shop->paymentTypes = self::getPaymentTypes($shop->id);
			$shop->customerGroups = self::getCustomerGroups();
			$shops[] = $shop;
		}
		return $shops;
	}

	private function getDeliveryTypes($shopId) {
		$carriers = Mage::getStoreConfig('carriers', $shopId);
		$methods = Mage::getSingleton('shipping/config')->getActiveCarriers($shopId);
		$deliveryTypes = array();
		foreach ($methods as $_ccode => $_carrier) {
			if ($carrierMethods = $_carrier->getAllowedMethods()) {
				if (!$_title = Mage::getStoreConfig("carriers/$_ccode/title", $shopId)) {
					$_title = $_ccode;
				}
				foreach ($carrierMethods as $_mcode => $_method) {
					$_code = $_ccode . '_' . $_mcode;
					$deliveryType = new CPDeliveryType();
					$deliveryType->id = $_code;
					$deliveryType->title = $_title . ' - ' . $_method;
					$deliveryType->active = (bool) $carriers[$_ccode]['active'];
					$deliveryTypes[] = $deliveryType;
				}
			}
		}
		return $deliveryTypes;
	}

	private function getPaymentTypes($shopId) {
		$paymentTypes = array();
		$payments = Mage::getSingleton('payment/config')->getActiveMethods();
		foreach ($payments as $paymentCode => $paymentModel) {
			if ($paymentCode === Mage::getSingleton('channelpilot/payment')->getCode()) {
				$types = Mage::getStoreConfig('payment/' . $paymentCode . '/types', $shopId);
				$cpmp_types = Mage::getSingleton('channelpilot/payment')->getCcTypes();
				foreach (explode(',', $types) as $type) {
					if (isset($cpmp_types[$type])) {
						$paymentType = new CPPaymentType();
						$paymentType->id = $type;
						$paymentType->title = $cpmp_types[$type];
						$paymentType->active = true;
						$paymentTypes[] = $paymentType;
					}
				}
			} else {

				$paymentTitle = Mage::getStoreConfig('payment/' . $paymentCode . '/title', $shopId);
				$paymentType = new CPPaymentType();
				$paymentType->id = $paymentCode;
				if (empty($paymentTitle)) {
					$paymentType->title = $paymentCode;
				} else {
					$paymentType->title = $paymentTitle;
				}
				$status = $paymentModel->canUseCheckout();
				if ($status == 1 && $paymentCode != 'free') {
					$paymentType->active = true;
				} else {
					$paymentType->active = false;
				}
				$paymentTypes[] = $paymentType;
			}
		}
		return $paymentTypes;
	}

	private function getCustomerGroups() {
		$customerGroups = array();
        $customerGroupCollection = Mage::getModel('customer/group')->getCollection();
		try {
			foreach ($customerGroupCollection AS $resultType) {
				$customerGroup = new CPCustomerGroupType();
				$customerGroup->id = $resultType->getId();
				$customerGroup->title = $resultType->getCustomerGroupCode();
				$customerGroup->active = true;
				$customerGroups[] = $customerGroup;
			}
		} catch (Exception $e) {
			CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, "Exception during register Shop: " . $e->getMessage(), "Exception during register Shop: " . $e->getMessage());
		}
		return $customerGroups;
	}

}

?>
