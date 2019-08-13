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
            $limit = Mage::app()->getRequest()->getParam('limit', 0);
			if ($limit) {
				try {
                    $shopId = self::getShopId($token);
                    /** @var  $collection Channelpilotsolutions_Channelpilot_Model_Resource_Order_Collection */
                    $collection = Mage::getModel('channelpilot/order')->getCollection()
                        ->addFieldToSelect(array('order_nr', 'marketplace'))
                        ->addHasShipmentFilter()
                        ->addFieldToFilter('main_table.status', array(
                            array('eq' => CPOrderStatus::ID_IMPORTED),
                            array('eq' => CPOrderStatus::ID_PARTIALLY_DELIVERED)
                        ))
                        ->addFieldToFilter('main_table.shop', array('eq' => $shopId));

                    $sResult = array();
                    $incomplete = 0;

                    // loop through all orders with deliveries and add every order with
                    // a completed delivery until the limit has been reached
                    foreach($collection as $order) {
                        /** @var  $salesOrder Mage_Sales_Model_Order */
                        $salesOrder = Mage::getModel('sales/order')->unsetData()->load($order['order_id']);

                        // For now the seller api does not allow partial deliveries.
                        // Therefore the variable $deliveryComplete will always be true.
                        if($salesOrder && $salesOrder->getId()) {
                            // order shipment is not complete yet, so skip this order
                            if($salesOrder->canShip()) {
                                $incomplete++;
                                continue;
                            }

                            $sResult[] = $order->getData();
                            $limit--;

                            if($limit == 0) {
                                break;
                            }
                        }
                    }

                    if($incomplete > 0) {
                        self::logError('Skipped '.$incomplete.' orders because the delivery is not completed yet.');
                    }

                    $deliveries = array();
                    $shipments = array();
					foreach ($sResult AS $order) {
                        $shipmentIds = explode(',',$order['shipment_ids']);
                        /** @var  $salesOrder Mage_Sales_Model_Order */
                        $salesOrder = Mage::getModel('sales/order')->unsetData()->load($order['order_id']);

                        // For now the seller api does not allow partial deliveries.
                        // Therefore the variable $deliveryComplete will always be true.
                        $deliveryComplete = true;

                        $delivered = new CPDelivery($order['order_nr'], $order['marketplace'], $deliveryComplete, $order['track_number'], date("Y-m-d", strtotime($order['shipment_created_at'])) . 'T' . date("H:i:s", strtotime($order['shipment_created_at'])));
                        $delivered->carrierName = $order['title'];
                        $delivered->shipping = new CPShipping();
                        $delivered->shipping->typeId = $order['shipping_method'];
                        $delivered->shipping->typeTitle = $order['title'];

                        $deliveredItems = array();
                        /** @var  $orderItem Mage_Sales_Model_Order_Item */
                        foreach($salesOrder->getAllItems() as $orderItem) {
                            $cpMarketplaceOrderItem = Mage::getModel('channelpilot/order_item')->getCollection()
                                ->addFieldToFilter('order_id', array('eq' => $order['order_id']))
                                ->addFieldToFilter('order_item_id', array('eq' => $orderItem->getId()))
                                ->getFirstItem();

                            $item = new CPOrderItem();

                            $item->id = $orderItem->getId();
                            $item->idExternal = $cpMarketplaceOrderItem->getMarketplaceOrderItemId();

                            $item->article = new CPArticle();
                            $item->article->id = $orderItem->getId();
                            $item->article->idExternal = $cpMarketplaceOrderItem->getMarketplaceOrderItemId();
                            $item->article->title = $orderItem->getName();

                            $item->quantityOrdered = $orderItem->getQtyOrdered();
                            $item->quantityDelivered = $orderItem->getQtyShipped();
                            $item->quantityCancelled = $orderItem->getQtyCanceled();

                            $item->costsSingle = new CPMoney();
                            $item->costsSingle->gross = $orderItem->getPriceInclTax();
                            $item->costsSingle->net = $orderItem->getPrice();
                            $item->costsSingle->tax = ($orderItem->getPriceInclTax() - $orderItem->getPrice());
                            $item->costsSingle->taxRate = $orderItem->getTaxPercent();

                            $item->costsTotal = new CPMoney();
                            $item->costsTotal->gross = $orderItem->getRowTotalInclTax();
                            $item->costsTotal->net = $orderItem->getRowTotal();
                            $item->costsTotal->tax = $orderItem->getTaxAmount();
                            $item->costsTotal->taxRate = $orderItem->getTaxPercent();

                            $item->feeSingleNet = 0;
                            $item->feeTotalNet = 0;

                            $deliveredItems[] = $item;
                        }

                        $delivered->deliveredItems = $deliveredItems;

                        $deliveries[] = $delivered;
                        $shipments[] = array(
                            'order_id' => $order['order_id'],
                            'order_nr' => $order['order_nr'], // this field is needed to be able to check for defective orders later
                            'shipment_id' => $order['shipment_ids'],
                        );
					}
					if (sizeof($deliveries) == 0) {
						self::hookResult(false);
					}

					$merchantId = self::getMerchantId($token);
					$api = new ChannelPilotSellerAPI_v3_2($merchantId, $token);
					$result = $api->registerDeliveries($deliveries);
					if ($result->header->resultCode == CPResultCodes::SUCCESS) {
						$defectiveOrderIncrementIds = self::changeStatusOrders($result->updateResults);
                        $shipmentsToInsert = array();
                        // remove 'order_nr' key for each shipment
                        foreach($shipments as $shipment) {
                            // if an error occured do not save this shipment
                            if(!in_array($shipment['order_nr'], $defectiveOrderIncrementIds)) {
                                $shipmentIds = explode(',', $shipment['shipment_id']);
                                foreach($shipmentIds as $shipmentId) {
                                    $shipmentsToInsert[] = array(
                                        'order_id' => $shipment['order_id'],
                                        'shipment_id' => $shipmentId,
                                    );
                                }
                            }
                        }
                        if(count($shipmentsToInsert) > 0) {
                            $insertedRows = Mage::getModel('channelpilot/order_shipment')->addMultipleShipments($shipmentsToInsert);
                        }

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
			if ($token) {
				CPErrorHandler::handle(CPErrors::RESULT_MISSING_PARAMS, "no token found", "no token found");
			} else {
				CPErrorHandler::handle(CPErrors::RESULT_FAILED, "ip not allowed by token: " . $token, "ip not allowed by token: " . $token);
			}
		}
		return "Error during handle deliveryHook";
	}

    protected function hookResult($moreAvailable) {
		$hook = new CPHookResponse();
		$hook->resultCode = CPResultCodes::SUCCESS;
		$hook->resultMessage = "DELIVERY HOOK SUCCESS";
		$hook->moreAvailable = $moreAvailable;
		$hook->writeResponse(self::defaultHeader, json_encode($hook));
	}

}

?>
