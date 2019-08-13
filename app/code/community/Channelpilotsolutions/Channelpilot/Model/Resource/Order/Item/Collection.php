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

class Channelpilotsolutions_Channelpilot_Model_Resource_Order_Item_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {

    /**
     * Model initialization
     *
     */
    protected function _construct() {
        $this->_init('channelpilot/order_item');
    }

    /**
     * Add a canceled sales order item filter to the collection.
     * @return Channelpilotsolutions_Channelpilot_Model_Resource_Order_Item_Collection
     */
    public function addCanceledSalesOrderItemsFilter() {
        $this->getSelect()
            ->joinLeft(array('cp_orders' => $this->getTable('channelpilot/order')),
                'cp_orders.order_id = main_table.order_id',
                array('cp_orders.order_nr', 'cp_orders.marketplace', 'cp_orders.status')
            )
            ->joinLeft(array('sales_order' => $this->getTable('sales/order')),
                'sales_order.entity_id = cp_orders.order_id',
                array('sales_order_state' => 'sales_order.state')
            )
            ->joinLeft(array('sales_order_item' => $this->getTable('sales/order_item')),
                'sales_order_item.item_id = main_table.order_item_id',
                array('sales_order_item.qty_refunded')
            )
            ->where('sales_order_item.qty_refunded > 0')
            ->where('sales_order.status != ?', Mage_Sales_Model_Order::STATE_CANCELED)
            ->where('main_table.cancelled != sales_order_item.qty_refunded')
            ->order('sales_order_item.order_id');

        return $this;
    }

    /**
     * Add a marketplace order filter
     * @param   string    $marketplaceOrderId
     * @param   string    $marketplace
     * @return  Channelpilotsolutions_Channelpilot_Model_Resource_Order_Item_Collection
     */
    public function addMarketplaceOrderFilter($marketplaceOrderId, $marketplace) {
        $this->getSelect()
            ->joinLeft(array('cp_orders' => $this->getTable('channelpilot/order')),
                'main_table.order_id = cp_orders.order_id',
                null
            )
            ->where('cp_orders.marketplace_order_id = ?', $marketplaceOrderId)
            ->where('cp_orders.marketplace = ?', $marketplace);

        return $this;
    }

    /**
     * @param   int    $shopId
     * @return  Channelpilotsolutions_Channelpilot_Model_Resource_Order_Item_Collection
     */
    public function addReadyForExportFilter($shopId) {
        $this->getSelect()
            ->joinLeft(array('cp_orders' => $this->getTable('channelpilot/order')),
                'cp_orders.order_id = main_table.order_id',
                array(
                    'orderId' => 'cp_orders.order_nr',
                    'externalOrderId' => 'cp_orders.marketplace_order_id',
                    'source' => 'cp_orders.marketplace',
                    'cp_orders.status'
                )
            )
            ->where('cp_orders.status = ?', CPOrderStatus::ID_READY_FOR_EXPORT)
            ->where('cp_orders.shop = ?', $shopId)
            ->order('cp_orders.order_nr');

        return $this;
    }
}