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

/**
 * Class Channelpilotsolutions_Channelpilot_Model_Order
 *
 * @method  string      getId()
 * @method  string      getOrderId()
 * @method  string      getOrderNr()
 * @method  string      getMarketplaceOrderId()
 * @method  string      getShop()
 * @method  int         getCreated()
 * @method  int         getStatus()
 */

class Channelpilotsolutions_Channelpilot_Model_Order extends Mage_Core_Model_Abstract {

    const CP_ORDER_UNPAID   = 0;
    const CP_ORDER_PAID     = 1;

    /**
     * Initialize resource model
     */
    protected function _construct() {
        $this->_init('channelpilot/order');
    }

    /**
     * Set the order_paid status for marketplace orders to "paid". Returns the number of affected rows
     * or boolean false in case the update query could not be executed.
     * @param   array   $orders
     * @return  int | bool
     */
    public function loadByMarketplaceOrderIdAndMarketplace($marketplaceOrderId, $marketplace) {
        return $this->_getResource()->loadByMarketplaceOrderIdAndMarketplace($marketplaceOrderId, $marketplace, $this);
    }

    /**
     * Set the order_paid status for marketplace orders. Returns the number of affected rows
     * or boolean false in case the update query could not be executed.
     * @param   array   $orders
     * @return  int | bool
     */
    public function massSetOrderPaid(array $orders) {
        return $this->getResource()->massSetOrderPaid($orders);
    }

    /**
     * Load by field order_nr
     * @param $orderNr
     * @return mixed
     */
    public function loadByOrderNr($orderNr) {
        return $this->_getResource()->loadByOrderNr($orderNr, $this);
    }
}