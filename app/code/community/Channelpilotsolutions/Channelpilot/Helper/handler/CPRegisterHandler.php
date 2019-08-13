<?php

/**
 * an cp register handler
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPRegisterHandler extends CPAbstractHandler {

	/**
	 * Handle register event
	 */
	public function handle() {
		$new = false;

        $multishopId = Mage::app()->getRequest()->getParam('multishopid', false);
        $merchantId = Mage::app()->getRequest()->getParam('merchantid', false);
        $token = Mage::app()->getRequest()->getParam('token', false);
        $ips = Mage::app()->getRequest()->getParam('ips', false);
        $method = Mage::app()->getRequest()->getParam('method', false);

		if ($multishopId && $merchantId && $token && $ips) {
			if (self::existShop($multishopId)) {

                $registration = Mage::getModel('channelpilot/registration');
                $data = array(
                    'last_stock_update'     => null,
                    'last_price_update'     => null,
                    'last_catalog_update'   => null,
                    'ips_authorized'        => $ips,
                    'merchantId'            => $merchantId,
                    'securityToken'         => $token,
                    'shopId'                => $multishopId,
                );

                try {
                    $registration->loadByShopIdAndToken($multishopId, $token);
                    $registrationId = $registration->getId();
                    $new = (empty($registrationId));
                    $registration->addData($data)
                        ->save();
                } catch (Exception $e) {
                    CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, "Exception during reregister Shop: " . $e->getMessage(), "Exception during reregister Shop: " . $e->getMessage());
                }
			} else {
                CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, "shop not found: " . $multishopId, "shop not found: " . $multishopId);
            }
		} else {
            CPErrorHandler::handle(CPErrors::RESULT_MISSING_PARAMS, "not enough parameter for method: " . $method, "not enough parameter for method: " . $method);
        }

		$hook = new CPRegisterHookResponse();
		$hook->ipsAllowed = $_SERVER['SERVER_ADDR'];
		$hook->resultCode = CPResultCodes::SUCCESS;
		if ($new == true) {
			$hook->resultMessage = "Shop registered";
		} else {
			$hook->resultMessage = "Shop reregistered";
		}
		$hook->writeResponse(self::defaultHeader, json_encode($hook));
	}

	/**
	 *
	 * @param type $registered
	 * @return boolean or error response
	 */
	public function reRegisterParameterSet($registered) {
        $reregistration = Mage::app()->getRequest()->getParam('reregistration', false);
		if ($reregistration && $reregistration == 'true') {
			return true;
		} else {
			if ($registered == true) {
				CPErrorHandler::handle(CPErrors::RESULT_ALREADY_REGISTERED, "shop already registered", "shop '" . Mage::app()->getRequest()->getParam('multishopid', '') . "' already registered");
			}
			return false;
		}
	}

	/**
	 *
	 * @param type $shopId
	 * @return boolean
	 */
	public function existShop($shopId) {
        $storeCollection = Mage::getModel('core/store')->getCollection()
            ->addFieldToFilter('store_id', array('eq' => $shopId));

        if(count($storeCollection) > 0) {
            return true;
        }

		CPErrorHandler::handle(CPErrors::RESULT_SHOP_UNKNOWN, "shop '" . $shopId . "' unknown", "shop '" . $shopId . "' unknown");
        return false;
	}

}

?>
