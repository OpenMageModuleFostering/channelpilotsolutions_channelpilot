<?php

/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License (GPL 3)
 * that is bundled with this package in the file LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Channelpilotsolutions_Channelpilot to newer
 * versions in the future. If you wish to customize Channelpilotsolutions_Channelpilot for your
 * needs please refer to http://www.channelpilot.com for more information.
 *
 * @category        Channelpilotsolutions
 * @package         Channelpilotsolutions_Channelpilot
 * @copyright       Copyright (c) 2012 <info@channelpilot.com> - www.channelpilot.com
 * @author          Björn Wehner <info@channelpilot.com>
 * @license         <http://www.gnu.org/licenses/> GNU General Public License (GPL 3)
 * @link            http://www.channelpilot.com
 */
class Channelpilotsolutions_Channelpilot_Block_Tracking_Tracking extends Mage_Core_Block_Template
{
    const   IMAGE_URL_CLICK       = 'https://click.cptrack.de/verify';
    const   IMAGE_URL_SALE        = 'https://sale.cptrack.de/';

    /**
     * Flag to wether use the click or sales tracking.
     * @var bool
     */
    protected $_isSale;

    public function __construct() {
        $this->_isSale = false;
    }

    /**
     * Check if tracking is enabled.
     * @return  bool
     */
    public function isEnabled() {
        return Mage::getStoreConfigFlag('channelpilot_tracking/channelpilot_tracking/channelpilot_useTracking');
    }

    /**
     * Check if the current tracking mode is set to "Image".
     * @return bool
     */
    public function isTrackingModeImage() {
        return Mage::getStoreConfig('channelpilot_tracking/channelpilot_tracking/method') == Channelpilotsolutions_Channelpilot_Model_Adminhtml_Source_Trackingmethod::TRACKING_METHOD_IMAGE;
    }

    /**
     * Get the tracking key for the current shop.
     * @return  mixed
     */
    public function getTrackingKey() {
        $storeId = Mage::app()->getStore()->getId();
        $trackingKeys = unserialize(Mage::getStoreConfig('channelpilot_tracking/channelpilot_tracking/channelpilot_trackingkeys'));
        foreach ($trackingKeys as $element) {
            if ($element['shop'] == $storeId) {
                return $element['trackingkey'];
            }
        }
    }

    /**
     * Get the order with the last increment id from the checkout session.
     * Returns false if the order could not be loaded.
     * @return Mage_Sales_Model_Order | bool
     */
    public function getOrder() {
        $lastIncrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($lastIncrementId);
        if($order && $order->getId()) {
            return $order;
        }

        Mage::log('Could not load order with increment id '.$lastIncrementId.' for sales tracking.');
        return false;
    }

    /**
     * Get the identifier field for the product (entity_id oder sku).
     * @return string
     */
    public function getProductIdField() {
        return Mage::getStoreConfig('channelpilot_general/channelpilot_general/channelpilot_articlenumber');
    }

    /**
     * Get the tracking cookie mode.
     * @return  string
     */
    public function getCookieMode() {
        return Mage::getStoreConfig('channelpilot_tracking/channelpilot_tracking/channelpilot_cookiemode');
    }

    /**
     * Get the url for the tracking image.
     * @return  string
     */
    public function getImageUrl() {
        if($this->_isSale) {
            $order = $this->getOrder();
            if($order) {
                $url = self::IMAGE_URL_SALE
                    . '?trackingKey='.urlencode($this->getTrackingKey())
                    . '&cookie='.urlencode($this->getCookieMode())
                    . '&orderId='.urlencode($order->getId())
                    . '&orderTotal='.urlencode($order->getGrandTotal() - $order->getTaxAmount());

                $productIdField = $this->getProductIdField();
                $i = 1;
                foreach($order->getItemsCollection(array(), true) as $item) {
                    $url .= '&id'.$i.'='.urlencode($item->getData($productIdField))
                    . '&price'.$i.'='.urlencode($item->getPrice())
                    . '&amount'.$i.'='.urlencode($item->getQtyOrdered());
                    $i++;
                }

                return $url;
            }
            return '';
        }
        return self::IMAGE_URL_CLICK;
    }

    /**
     * Set the isSale flag. If the param is set to anything that can be interpreted as
     * true, the class variable _isSale is set to true.
     * @param   bool|false $isSale
     */
    public function setIsSale($isSale = false) {
        if($isSale) {
            $this->_isSale = true;
        }
    }
}