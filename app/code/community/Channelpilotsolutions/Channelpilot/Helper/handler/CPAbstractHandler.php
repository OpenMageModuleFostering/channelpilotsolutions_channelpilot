<?php

/**
 * an cp abstract handler
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPAbstractHandler {

	const defaultHeader = "Content-Type: application/json;";
	const errorHeader_forbidden = "HTTP/1.0 403 Forbidden";
	const ChannelPilot_IP = "148.251.65.130";
	const DB_REGISTRATION = "cp_registration";
	const DB_PRICES = "cp_prices";
	const DB_ORDERS = "cp_marketplace_orders";
	const DB_ORDER_ITEMS = "cp_marketplace_order_items";

	/**
	 * Is the IP allowed for this shopId
	 *
	 * @param type $shopId
	 * @return boolean
	 */
	public static function isIpAllowedViaShopId($shopId) {
        $clientIp = Mage::app()->getRequest()->getClientIp();
		if (self::ChannelPilot_IP == $clientIp || !Mage::getStoreConfig('channelpilot_general/channelpilot_general/channelpilot_checkIp')) {
			return true;
		} else {
			return in_array($clientIp, Mage::getModel('channelpilot/registration')->getAllowedIpsViaShopId($shopId));
		}
	}

	/**
	 * Is the IP allowed for this securityToken.
     * The param $useConfigSettingCheckIp is currently use by the CpShipping carrier to prevent PayPal from
     * using this shipping method if set to "backend_only". If the param is set to false the step to return true
     * if the config setting to check the ip is set to false will be skipped.
	 *
	 * @param string $token
     * @param bool $useConfigSettingCheckIp
	 * @return boolean
	 */
	public static function isIpAllowedViaSecurityToken($token, $useConfigSettingCheckIp = true) {
        $clientIp = Mage::app()->getRequest()->getClientIp();
		if (self::ChannelPilot_IP == $clientIp) {
			return true;
		} else if($useConfigSettingCheckIp && !Mage::getStoreConfig('channelpilot_general/channelpilot_general/channelpilot_checkIp')) {
            return true;
        } else {
			return in_array($clientIp, Mage::getModel('channelpilot/registration')->getAllowedIpsViaSecurityToken($token));
		}
	}

	/**
	 * Is the shop still registered?
	 *
	 * @param integer $shopId
	 * @return boolean
	 */
	public static function isShopRegistered($shopId, $token = null) {
        $collection = Mage::getSingleton('channelpilot/registration')->getCollection()
            ->addFieldToSelect(array('shopId', 'securityToken'))
            ->addFieldToFilter('shopId', array('eq' => $shopId));

        // if token is not null check if a shop with this token is registered otherwise all shops with $shopId
        if($token !== null) {
            $collection->addFieldToFilter('securityToken', array('eq' => $token));
        }

        return (count($collection) > 0);
	}

    /**
     * Get the merchantId for a security token.
     * @param   string  $token
     * @return  mixed
     */
	public static function getMerchantId($token) {
        $merchantId = Mage::getModel('channelpilot/registration')->load($token, 'securityToken')
            ->getData('merchantId');

        if(empty($merchantId)) {
            CPErrorHandler::handle(CPErrors::RESULT_FAILED,'No merchant id found for token.','No merchant id found for token.');
        }

        return $merchantId;
	}

	/**
	 * Get shopId by token for registered shop.
	 *
	 * @param   string    $token
	 * @return  mixed
	 */
	public static function getShopId($token) {
        $shopId = Mage::getModel('channelpilot/registration')->load($token, 'securityToken')
            ->getData('shopId');

        if(empty($shopId)) {
            CPErrorHandler::handle(CPErrors::RESULT_FAILED,'No shop id found for token.','No shop id found for token.');
        }

        return $shopId;
	}

	public static function changeStatusOrders($apiOrders) {
        $defectiveOrderIncrementIds = array();
		foreach ($apiOrders as $apiOrder) {
			if ($apiOrder->header->resultCode == CPResultCodes::SUCCESS) {
				self::changeStatusOrder($apiOrder->orderHeader);
			} else {
                $defectiveOrderIncrementIds[] = $apiOrder->orderHeader->orderId;
				self::logError("Cannot change orderstatus from order (id: '" . $apiOrder->orderHeader->orderId . "', status: '" . $apiOrder->orderHeader->status->identifier . ", msg: ". $apiOrder->header->resultMessage .")");
			}
		}
        return $defectiveOrderIncrementIds;
	}

	public static function changeStatusOrder($apiOrderHeader) {
        if(!empty($apiOrderHeader->orderId)) {
            $order = Mage::getModel('channelpilot/order')->loadByOrderNr($apiOrderHeader->orderId);
            if($order && $order->getId()) {
                $order->setData('status', $apiOrderHeader->status->identifier);
                try {
                    $order->save();
                } catch (Exception $e) {
                    CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, "Exception during changeStatusOrder: " . $e->getMessage(), "Exception during update cp_marketplace_orders: ". $apiOrderHeader->status->identifier . "\n" . $e->getMessage());
                }
            } else {
                $header = print_r($apiOrderHeader, true);
                CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, "error loading order: ".$header, "error loading order: ".$header);
            }
        }
	}

	/**
	 * log the error in the cp_marketplace - log file
	 *
	 * @param string $msg
	 */
	public static function logError($msg) {
        $clientIp = Mage::app()->getRequest()->getClientIp();
		$msg = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI] by IP: {$clientIp}\n$msg";

		Mage::log("$msg\n\n", null, 'cp_plugin.log');
        Mage::getModel('channelpilot/logs')
            ->unsetData()
            ->setData(array('created' => date('Y-m-d H:i:s'), 'content' => $msg))
            ->save();
	}
}

?>
