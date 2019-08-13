<?php

/**
 * an cp delivery handler
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPDeliveryHandler extends CPAbstractHandler {

	/**
	 * Handle delivery hook.
	 * @return type
	 */
	public function handle() {
        $method = Mage::app()->getRequest()->getParam('method', false);
        $token = Mage::app()->getRequest()->getParam('token', false);
		if ($token && self::isIpAllowedViaSecurityToken($token)) {
			self::checkConfig();
            $limit = Mage::app()->getRequest()->getParam('limit', 0);
			if ($limit) {
				try {

                    $sResult = Mage::getModel('channelpilot/order')->getCollection()
                        ->addFieldToSelect(array('order_nr', 'marketplace'))
                        ->addHasShipmentFilter()
                        ->addFieldToFilter('main_table.status', array(
                            array('eq' => CPOrderStatus::ID_IMPORTED),
                            array('eq' => CPOrderStatus::ID_PARTIALLY_DELIVERED)
                        ))
                        ->setPageSize($limit)
                        ->getData();

                    $deliveries = array();
                    $shipments = array();
					foreach ($sResult AS $order) {
                        $shipmentIds = explode(',',$order['shipment_ids']);
                        $salesOrder = Mage::getModel('sales/order')->unsetData()->load($order['order_id']);
                        $deliveryComplete = true;
                        if($salesOrder && $salesOrder->getId()) {
                            $deliveryComplete = (!$salesOrder->canShip());
                        }
                        foreach($shipmentIds as $shipmentId) {
                            $delivered = new CPDelivery($order['order_nr'], $order['marketplace'], $deliveryComplete, $order['track_number'], date("Y-m-d", strtotime($order['shipment_created_at'])) . 'T' . date("H:i:s", strtotime($order['shipment_created_at'])));
                            $delivered->carrierName = $order['title'];
                            $delivered->shipping = new CPShipping();
                            $delivered->shipping->typeId = $order['shipping_method'];
                            $delivered->shipping->typeTitle = $order['title'];
                            $deliveries[] = $delivered;
                            $shipments[] = array(
                                'order_id' => $order['order_id'],
                                'shipment_id' => $shipmentId,
                            );
                        }
					}
					if (sizeof($deliveries) == 0) {
						self::hookResult(false);
					}

					$merchantId = self::getMerchantId($token);
					$api = new ChannelPilotSellerAPI_v1_0($merchantId, $token);
					$result = $api->registerDeliveries($deliveries);
					if ($result->header->resultCode == CPResultCodes::SUCCESS) {
						self::changeStatusOrders($result->updateResults);
                        $insertedRows = Mage::getModel('channelpilot/order_shipment')->addMultipleShipments($shipments);

					} else {
						//	Result from registerDeliveries has no success
						self::logError("request registerDeliveries() resultCode " . $result->header->resultCode);
						$hook = new CPHookResponse();
						$hook->resultCode = CPResultCodes::SUCCESS;
						$hook->resultMessage = "request registerDeliveries() resultCode " . $result->header->resultCode;
						$hook->moreAvailable = false;
						$hook->apiResultCode = $result->header->resultCode;
						$hook->writeResponse(self::defaultHeader, json_encode($hook));
					}
					self::hookResult(true);
				} catch (Exception $e) {
					CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, "Exception in handle DeliveryHook: " . $e->getMessage(), "Exception in handle DeliveryHook: " . $e->getMessage());
				}
			} else {
				CPErrorHandler::handle(CPErrors::RESULT_MISSING_PARAMS, "no limit set for method: " . $method, "no limit set for method: " . $method);
			}
		} else {
			if (empty($_GET['token'])) {
				CPErrorHandler::handle(CPErrors::RESULT_MISSING_PARAMS, "no token found", "no token found");
			} else {
				CPErrorHandler::handle(CPErrors::RESULT_FAILED, "ip not allowed by token: " . $token, "ip not allowed by token: " . $token);
			}
		}
		return "Error during handle deliveryHook";
	}

	private function hookResult($moreAvailable) {
		$hook = new CPHookResponse();
		$hook->resultCode = CPResultCodes::SUCCESS;
		$hook->resultMessage = "DELIVERY HOOK SUCCESS";
		$hook->moreAvailable = $moreAvailable;
		$hook->writeResponse(self::defaultHeader, json_encode($hook));
	}

}

?>
