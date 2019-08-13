<?php

// include the stub-classes
require_once 'thin/CPAuth.php';
require_once 'thin/CPResponseHeader.php';
require_once 'thin/CPAddress.php';
require_once 'thin/CPArticle.php';
require_once 'thin/CPCancellation.php';
require_once 'thin/CPManagedArticlePrice.php';
require_once 'thin/CPMoney.php';
require_once 'thin/CPPayment.php';
require_once 'thin/CPCustomer.php';
require_once 'thin/CPCustomerGroup.php';
require_once 'thin/CPDelivery.php';
require_once 'thin/CPOrderItem.php';
require_once 'thin/CPShipping.php';
require_once 'thin/CPOrderStatus.php';
require_once 'thin/CPOrderHeader.php';
require_once 'thin/CPOrder.php';
require_once 'thin/CPOrderSummary.php';
require_once 'thin/CPRefund.php';

// request-classes
// response-classes
require_once 'responses/Response.php';
require_once 'responses/GetServerTimeResponse.php';
require_once 'responses/UpdateOrdersResponse.php';
require_once 'responses/UpdateOrderResult.php';
require_once 'responses/GetNewMarketplaceOrdersResponse.php';
require_once 'responses/GetManagedArticlePricesResponse.php';

/**
 * Main API-Class
 * @author    Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 3.2
 */
class ChannelPilotSellerAPI_v3_2 extends SoapClient {

    private $auth;
    private $soapOptions = array(
        'connection_timeout' => 20,
        'features' => SOAP_SINGLE_ELEMENT_ARRAYS
    );
    private $classmap = array(
        'CPAuth' => 'CPAuth',
        'CPResponseHeader' => 'CPResponseHeader',
        'AbstractResponse' => 'AbstractResponse',
        'GetServerTimeResponse' => 'GetServerTimeResponse',
        'CPArticleUpdate' => 'CPArticleUpdate',
        'UpdateArticlesResponse' => 'UpdateArticlesResponse',
        'UpdateArticleResult' => 'UpdateArticleResult',
        'UpdateOrdersResponse' => 'UpdateOrdersResponse',
        'UpdateOrderResult' => 'UpdateOrderResult',
        'CPAddress' => 'CPAddress',
        'CPArticle' => 'CPArticle',
        'CPManagedArticlePrice' => 'CPManagedArticlePrice',
        'CPMoney' => 'CPMoney',
        'CPPayment' => 'CPPayment',
        'CPCustomer' => 'CPCustomer',
        'CPOrderItem' => 'CPOrderItem',
        'CPShipping' => 'CPShipping',
        'CPOrderStatus' => 'CPOrderStatus',
        'CPOrderHeader' => 'CPOrderHeader',
        'CPOrder' => 'CPOrder',
        'CPOrderSummary' => 'CPOrderSummary',
        'GetNewMarketplaceOrdersResponse' => 'GetNewMarketplaceOrdersResponse',
        'GetManagedArticlePricesResponse' => 'GetManagedArticlePricesResponse',
        'CPRefund' => 'CPRefund'
    );

    public function __construct($merchantId, $shopToken) {
        $this->auth = new CPAuth($merchantId, $shopToken);

        foreach ($this->classmap as $key => $value) {
            if (!isset($this->soapOptions['classmap'][$key])) {
                $this->soapOptions['classmap'][$key] = $value;
            }
        }
        parent::__construct($this->getWsdlUrl(), $this->soapOptions);
    }

    /**
     * Receives the acutal server time. Can be used to test the connection.
     * @return GetServerTimeResponse
     */
    public function getServerTime() {
        return $this->__call(
            'getServerTime',
            array(
                new SoapParam($this->auth, 'auth')
            )
        );
    }

    /**
     * retrieves new marketplace orders
     * @return GetNewMarketplaceOrdersResponse
     */
    public function getNewMarketplaceOrders() {
        return $this->__call(
            'getNewMarketplaceOrders',
            array(
                new SoapParam($this->auth, 'auth')
            )
        );
    }

    /**
     * update orders in ChannelPilot to "imported", generates the matching between externalOrderId and the shop-internal orderId
     * q
     * @return GetNewMarketplaceOrdersResponse
     */


    /**
     * update orders in ChannelPilot to "imported", generates the matching between externalOrderId and the shop-internal orderId
     * @param array $orders array of CPOrders
     * @param type $mapOrderItemIds boolean, if channelPilot should map your internal orderItemIds
     * @return type
     */
    public function setImportedOrders(array $orders, $mapOrderItemIds) {
        return $this->__call(
            'setImportedOrders',
            array(
                new SoapParam($this->auth, 'auth'),
                new SoapParam($orders, 'importedOrders'),
                new SoapParam($mapOrderItemIds, 'mapOrderItemIds')
            )
        );
    }


    public function registerDeliveries(array $deliveries) {
        return $this->__call(
            'registerDeliveries',
            array(
                new SoapParam($this->auth, 'auth'),
                new SoapParam($deliveries, 'deliveries')
            )
        );
    }

    public function registerCancellations(array $cancellations) {
        return $this->__call(
            'registerCancellations',
            array(
                new SoapParam($this->auth, 'auth'),
                new SoapParam($cancellations, 'cancellations')
            )
        );
    }

    public function getDynamicArticlePrices($priceId, $method, $filterArticles, $filterFrom) {
        return $this->__call(
            'getDynamicArticlePrices',
            array(
                new SoapParam($this->auth, 'auth'),
                new SoapParam($priceId, 'priceId'),
                new SoapParam(null, 'pagination'),
                new SoapParam($method, 'method'),
                new SoapParam($filterArticles, 'filterArticles'),
                new SoapParam($filterFrom, 'filterFrom')
            )
        );
    }

    /**
     * Set paymentTime in ChannelPilot. Send CPOrder with CPOrderHeader and CPPayment (paymentTime is necessary).
     * @param CPOrder[] $orders
     * @return UpdateOrdersResponse
     */
    public function setPaidOrders(array $orders) {
        return $this->__call(
            'setPaidOrders', array(
                new SoapParam($this->auth, 'auth'),
                new SoapParam($orders, 'paidOrders')
            )
        );
    }
    
    /**
     *
     *
     */
    public function registerRefunds(array $refunds) {
        return $this->__call(
            'registerRefunds', array(
                new SoapParam($this->auth, 'auth'),
                new SoapParam($refunds, 'refunds')
            )     
        );
    }
	
	/**
     * @return string
     */
    public function getWsdlUrl()
    {
        return Mage::getStoreConfig('channelpilot_general/channelpilot_general/seller_api_wsdl_url');
    }
}
