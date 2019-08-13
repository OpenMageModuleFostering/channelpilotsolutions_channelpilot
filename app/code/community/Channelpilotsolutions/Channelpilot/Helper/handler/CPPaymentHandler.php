<?php


/**
 * an cp payment handler
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPPaymentHandler extends CPAbstractHandler {
    /**
     * Bestellung wurde bezahlt.
     * Handle Payment Hook
     * @return string
     */
    public function handle() {
        $token = Mage::app()->getRequest()->getParam('token', false);
        $method = Mage::app()->getRequest()->getParam('method', false);
        if ($token && self::isIpAllowedViaSecurityToken($token)) {
            $limit = Mage::app()->getRequest()->getParam('limit', false);
            if ($limit) {
                $shopId = self::getShopId($token);
                $sResult = Mage::getModel('channelpilot/order')->getCollection()
                    ->addFieldToSelect(array('marketplace_order_id', 'marketplace', 'status'))
                    ->addIsPaidFilter()
                    ->addFieldToFilter('main_table.status', array('eq' => CPOrderStatus::ID_IMPORTED))
                    ->addFieldToFilter('main_table.order_paid', array('eq' => Channelpilotsolutions_Channelpilot_Model_Order::CP_ORDER_UNPAID))
                    ->addFieldToFilter('main_table.shop', array('eq' => $shopId))
                    ->setPageSize((int)$limit)
                    ->getData();

                try {
                    $orders = array();
                    foreach ($sResult AS $order) {
                        $paymentTimeFormatted = date("Y-m-d\TH:i:s");
                        $cpOrder = new CPOrder();
                        $cpOrder->orderHeader = new CPOrderHeader($order['marketplace_order_id'], $order['order_id'], $order['marketplace'], $order['status'], false, null);
                        $cpOrder->payment = new CPPayment($paymentTimeFormatted);
//                        unset($cpOrder->payment->$paymentTimeFormatted); // TODO mit Peter besprechen (???)
                        $cpOrder->payment->paymentTime = $paymentTimeFormatted;
                        $orders[] = $cpOrder;
                    }
                    $paidOrders = $orders;

                    if (sizeof($paidOrders) == 0) {
                        self::hookResult(false);
                    }
                    $merchantId = self::getMerchantId($token);
                    $api = new ChannelPilotSellerAPI_v3_2($merchantId, $token);
                    $result = $api->setPaidOrders($paidOrders);
                    if ($result->header->resultCode == CPResultCodes::SUCCESS) {
                        $affectedRows = Mage::getModel('channelpilot/order')->massSetOrderPaid($paidOrders);
                    } else {
                        //	Result from setPaidOrders has no success
                        self::logError("request setPaidOrders() resultCode " . $result->header->resultCode);
                        $hook = new CPHookResponse();
                        $hook->resultCode = CPResultCodes::SUCCESS;
                        $hook->resultMessage = "request setPaidOrders() resultCode " . $result->header->resultCode;
                        $hook->moreAvailable = false;
                        $hook->apiResultCode = $result->header->resultCode;
                        $hook->writeResponse(self::defaultHeader, json_encode($hook));
                    }
                    self::hookResult(true);
                } catch (Exception $e) {
                    CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, "Exception in handle PaymentsHook: " . $e->getMessage(), "Exception in handle PaymentsHook: " . $e->getMessage());
                }
            } else {
                CPErrorHandler::handle(CPErrors::RESULT_MISSING_PARAMS, "no limit set for method: " . $method, "no limit set for method: " . $method);
            }
        } else {
            if (empty($token)) {
                CPErrorHandler::handle(CPErrors::RESULT_MISSING_PARAMS, "no token found", "no token found");
            } else {
                CPErrorHandler::handle(CPErrors::RESULT_FAILED, "ip not allowed by token: " . $token, "ip not allowed by token: " . $token);
            }
        }
        return "Error during handle paymentHook";
    }

    protected function hookResult($moreAvailable) {
        $hook = new CPHookResponse();
        $hook->resultCode = CPResultCodes::SUCCESS;
        $hook->resultMessage = "PAYMENT HOOK SUCCESS";
        $hook->moreAvailable = $moreAvailable;
        $hook->writeResponse(self::defaultHeader, json_encode($hook));
    }
}
?>
