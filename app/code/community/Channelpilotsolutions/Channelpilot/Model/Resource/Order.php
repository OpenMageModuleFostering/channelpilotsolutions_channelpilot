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

class Channelpilotsolutions_Channelpilot_Model_Resource_Order extends Mage_Core_Model_Resource_Db_Abstract {

    /**
     * Model Initialization
     *
     */
    protected function _construct() {
        $this->_init('channelpilot/order', 'order_id');
        $this->_isPkAutoIncrement = false;
    }

    /**
     * Load model by marketplace_order_id and marketplace.
     * @param       string      $marketplaceOrderId
     * @param       string      $marketplace
     * @param       Channelpilotsolutions_Channelpilot_Model_Order $order
     * @return      Channelpilotsolutions_Channelpilot_Model_Order
     */
    public function loadByMarketplaceOrderIdAndMarketplace($marketplaceOrderId, $marketplace, Channelpilotsolutions_Channelpilot_Model_Order $order) {
        $read = $this->_getReadAdapter();
        if($read) {
            $select = $read->select()
                ->from(array('order' => $this->getMainTable()))
                ->where('order.marketplace_order_id = ?', $marketplaceOrderId)
                ->where('order.marketplace = ?', $marketplace);

            $result = $read->fetchRow($select);
            if(!empty($result)) {
                $order->setData($result);
            }
        }

        return $order;
    }

    /**
     * Set the order_paid status for marketplace orders to "paid". Returns the number of affected rows
     * or boolean false in case the update query could not be executed.
     * @param   array   $orders
     * @return  int | bool
     */
    public function massSetOrderPaid(array $orders) {
        $write = $this->_getWriteAdapter();
        if($write) {
            $orderIds = array();

            foreach($orders as $order) {
                $orderIds[] = $write->quote($order->orderHeader->orderId);
            }

            $sOrderIds = implode(',', $orderIds);
            return $write->update($this->getMainTable(),
                array('order_paid' => Channelpilotsolutions_Channelpilot_Model_Order::CP_ORDER_PAID),
                'order_id IN('.$sOrderIds.')'
            );
        }

        return false;
    }

    /**
     * Load order by field order_nr
     * @param $orderNr
     * @param Channelpilotsolutions_Channelpilot_Model_Order $order
     * @return Channelpilotsolutions_Channelpilot_Model_Order
     */
    public function loadByOrderNr($orderNr, Channelpilotsolutions_Channelpilot_Model_Order $order) {
        $read = $this->_getReadAdapter();
        if($read) {
            $select = $read->select()
                ->from(array('order' => $this->getMainTable()))
                ->where('order.order_nr = ?', $orderNr);

            $result = $read->fetchRow($select);
            if(!empty($result)) {
                $order->setData($result);
            }
        }

        return $order;
    }
}