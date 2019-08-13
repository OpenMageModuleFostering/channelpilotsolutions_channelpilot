<?php

/**
 * an cp cancellation handler
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPCancellationHandler extends CPAbstractHandler {

    protected $_shopId;

	/**
	 * Handle cancellation hook.
	 * @return type
	 */
	public function handle() {
        $token = Mage::app()->getRequest()->getParam('token', false);
        $method = Mage::app()->getRequest()->getParam('method', '');
		if ($token && self::isIpAllowedViaSecurityToken($token)) {
            $this->_shopId = self::getShopId($token);
			$cancelled = array();

			$cancelledOrders = $this->getCancelledOrders();
			foreach ($cancelledOrders as $orders) {
				if (isset($orders)) {
					$cancelled[] = $orders;
				}
			}
			$cancelledOrderItems = $this->getCancelledItems();

			foreach ($cancelledOrderItems as $orders) {
				if (isset($orders)) {
					$cancelled[] = $orders;
				}
			}

			if (sizeof($cancelled) == 0) {
				self::hookResult(false);
			}
			$merchantId = self::getMerchantId($token);
			$api = new ChannelPilotSellerAPI_v1_0($merchantId, $token);
			$result = $api->registerCancellations($cancelled);
			if ($result->header->resultCode == CPResultCodes::SUCCESS) {
				self::changeStatusOrders($result->updateResults);
                try {
                    Mage::getModel('channelpilot/order_item')->updateCancelledQty($cancelledOrderItems);
                } catch(Exception $e) {
                    CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, "Exception in handle CancellationHook: " . $e->getMessage(), "Exception in handle CancellationHook: " . $e->getMessage());
                }
			} else {
				//	Result from registerDeliveries has no success
				self::logError("request registerCancellations() resultCode " . $result->header->resultCode);
				$hook = new CPHookResponse();
				$hook->resultCode = CPResultCodes::SUCCESS;
				$hook->resultMessage = "request registerCancellations() resultCode " . $result->header->resultCode;
				$hook->moreAvailable = false;
				$hook->apiResultCode = $result->header->resultCode;
				$hook->writeResponse(self::defaultHeader, json_encode($hook));
			}
			self::hookResult(true);
		} else {
			if (empty($token
            )) {
				CPErrorHandler::handle(CPErrors::RESULT_MISSING_PARAMS, "no token found", "no token found");
			} else {
				CPErrorHandler::handle(CPErrors::RESULT_FAILED, "ip not allowed by token: " . $token, "ip not allowed by token: " . $token);
			}
		}
	}

	protected function hookResult($moreAvailable) {
		$hook = new CPHookResponse();
		$hook->resultCode = CPResultCodes::SUCCESS;
		$hook->resultMessage = "CANCELLATION HOOK SUCCESS";
		$hook->moreAvailable = $moreAvailable;
		$hook->writeResponse(self::defaultHeader, json_encode($hook));
	}

    protected function getCancelledItems() {
        $sResult = Mage::getModel('channelpilot/order_item')->getCollection()
            ->addFieldToSelect(array('order_item_id', 'marketplace_order_item_id', 'time' => new Zend_Db_Expr('NOW()')))
            ->addCanceledSalesOrderItemsFilter()
            ->addFieldToFilter('cp_orders.shop', array('eq' => $this->_shopId))
            ->getData();

		try {
			$order = null;
			$orderId = null;
			$orders = array();
			foreach ($sResult AS $result) {
				if ($orderId == null || $orderId != $result['order_nr']) {
					if ($orderId != null) {
						$orders[] = $order;
					}
                    $isWholeOrderCanceled = ($result['sales_order_state'] == Mage_Sales_Model_Order::STATE_CLOSED);
					$order = new CPCancellation($result['order_nr'], $result['marketplace'], $result['status'], date("Y-m-d", strtotime($result['time'])) . 'T' . date("H:i:s", strtotime($result['time'])), $isWholeOrderCanceled);
					$orderId = $result['order_nr'];
				}
				$item = new CPOrderItem();
				$item->id = $result['order_item_id'];
				$item->idExternal = $result['marketplace_order_item_id'];
				$item->quantityCancelled = $result['qty_refunded'];
				$order->cancelledItems[] = $item;
			}
			$orders[] = $order;
			return $orders;
		} catch (Exception $e) {
			CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, "Exception in handle CancellationHook: " . $e->getMessage(), "Exception in handle CancellationHook: " . $e->getMessage());
		}
	}

    /**
     * Update the channelpilot/order_item table to set the cancelled column.
     * @param array $orders
     */
    protected function _updateMarketplaceOrderItems(array $orders = array()) {
        foreach($orders as $order) {
            foreach($order->cancelledItems as $cpOrderItem) {
                if($cpOrderItem->quantityCancelled > 0) {
                    Mage::getModel('channelpilot/order_item')->unsetData()
                        ->load($cpOrderItem->id)
                        ->setData('cancelled', $cpOrderItem->quantityCancelled)
                        ->save();
                }
            }
        }
    }

    protected function getCancelledOrders() {
		try {
            $result = Mage::getModel('channelpilot/order')->getCollection()
                ->addFieldToSelect(array('order_nr', 'marketplace', 'time' => new Zend_Db_Expr('NOW()'), 'status'))
                ->addFieldToFilter('main_table.status', array('neq' => CPOrderStatus::ID_CANCELLED))
                ->addFieldToFilter('main_table.shop', array('eq' => $this->_shopId))
                ->addCancelledSalesOrderFilter()
                ->getData();

			$orders = array();
			foreach ($result AS $order) {
				$cancelled = new CPCancellation($order['order_nr'], $order['marketplace'], $order['status'], date("Y-m-d", strtotime($order['time'])) . 'T' . date("H:i:s", strtotime($order['time'])), true);
				$orders[] = $cancelled;
			}
			return $orders;
		} catch (Exception $e) {
			CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, "Exception in handle CancellationHook: " . $e->getMessage(), "Exception in handle CancellationHook: " . $e->getMessage());
		}
	}
}

?>
