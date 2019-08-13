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
		if (self::ChannelPilot_IP == $_SERVER['REMOTE_ADDR'] || !Mage::getStoreConfig('channelpilot_general/channelpilot_general/channelpilot_checkIp')) {
			return true;
		} else {
			return in_array($_SERVER['REMOTE_ADDR'], Mage::getModel('channelpilot/registration')->getAllowedIpsViaShopId($shopId));
		}
	}

	/**
	 * Is the IP allowed for this securityToken
	 *
	 * @param type $token
	 * @return boolean
	 */
	public static function isIpAllowedViaSecurityToken($token) {
		if (self::ChannelPilot_IP == $_SERVER['REMOTE_ADDR'] || !Mage::getStoreConfig('channelpilot_general/channelpilot_general/channelpilot_checkIp')) {
			return true;
		} else {
			return in_array($_SERVER['REMOTE_ADDR'], Mage::getModel('channelpilot/registration')->getAllowedIpsViaSecurityToken($token));
		}
	}

	/**
	 * Is the shop still registered?
	 *
	 * @param integer $shopId
	 * @return boolean
	 */
	public static function isShopRegistered($shopId) {
        $collection = Mage::getSingleton('channelpilot/registration')->getCollection()
            ->addFieldToSelect('shopId')
            ->addFieldToFilter('shopId', array('eq' => $shopId));

        return (count($collection) > 0);
	}

    /**
     * Get the merchantId for a security token.
     * Returns NULL if record could not be found.
     * @param   string  $token
     * @return  mixed
     */
	public static function getMerchantId($token) {
        return Mage::getModel('channelpilot/registration')->load($token, 'securityToken')
            ->getData('merchantId');
	}

	/**
	 * Get shopId by token for registered shop.
     * Returns NULL if no record could be found.
	 *
	 * @param   string    $token
	 * @return  mixed
	 */
	public static function getShopId($token) {
        return Mage::getModel('channelpilot/registration')->load($token, 'securityToken')
            ->getId();
	}

	public static function changeStatusOrders($apiOrders) {
		foreach ($apiOrders as $apiOrder) {
			if ($apiOrder->header->resultCode == CPResultCodes::SUCCESS) {
				self::changeStatusOrder($apiOrder->orderHeader);
			} else {
				self::logError("Cannot change orderstatus from order (id: '" . $apiOrder->orderHeader->orderId . "', status: '" . $apiOrder->orderHeader->status->identifier . "')");
			}
		}
	}

	public static function changeStatusOrder($apiOrderHeader) {
        if(!empty($apiOrderHeader->oderId)) {
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
		$msg = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI] by IP: {$_SERVER['REMOTE_ADDR']}\n$msg";

		Mage::log("$msg\n\n", null, 'cp_plugin.log');
        Mage::getModel('channelpilot/logs')
            ->unsetData()
            ->setData(array('created' => date('Y-m-d H:i:s'), 'content' => $msg))
            ->save();
	}

	public static function checkConfig() {
		/* if(oxConfig::getInstance()->getShopConfVar('CPMARKETPLACE_ART_NUMBER')==2 && oxConfig::getInstance()->getShopConfVar('CPMARKETPLACE_ART_OTHERARTNUM')==''){
		  CPErrorHandler::handle(CPErrors::RESULT_CONFIG_INVALID, "No column for other article number", "No column for other article number");
		  }
		  if(oxConfig::getInstance()->getShopConfVar('CPMARKETPLACE_IMPORT')==''){
		  CPErrorHandler::handle(CPErrors::RESULT_CONFIG_INVALID, "No folder for unpaid orders", "No folder for unpaid orders");
		  }
		  if(oxConfig::getInstance()->getShopConfVar('CPMARKETPLACE_PAIDIMPORT')==''){
		  CPErrorHandler::handle(CPErrors::RESULT_CONFIG_INVALID, "No folder for paid orders", "No folder for paid orders");
		  }
		  if(oxConfig::getInstance()->getShopConfVar('CPMARKETPLACE_CANCEL')==''){
		  CPErrorHandler::handle(CPErrors::RESULT_CONFIG_INVALID, "No folder for cancelled orders", "No folder for cancelled orders");
		  } */
	}

}

?>
