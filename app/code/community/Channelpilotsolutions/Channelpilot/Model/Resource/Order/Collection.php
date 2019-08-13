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

class Channelpilotsolutions_Channelpilot_Model_Resource_Order_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {

    /**
     * Model initialization
     *
     */
    protected function _construct() {
        $this->_init('channelpilot/order');
    }

    /**
     * Add a "sales order has shipment" filter to the collection.
     * @return Channelpilotsolutions_Channelpilot_Model_Resource_Order_Collection
     */
    public function addHasShipmentFilter() {
        $read = $this->getResource()->getReadConnection();

        if($read) {
            $select = $read->select()
                ->from($this->getTable('channelpilot/order_shipment'), array('shipment_id'));

            $quotedShipmentEntityId = $read->quoteIdentifier('shipment.entity_id');

            $this->getSelect()
                ->joinLeft(array('shipment' => $this->getTable('sales/shipment')),
                    'shipment.order_id = main_table.order_id AND shipment.entity_id NOT IN('.$select->__toString().')',
                    array(
                        'shipment_created_at' => 'shipment.created_at',
                        'shipment_ids' => new Zend_Db_Expr('GROUP_CONCAT(DISTINCT '.$quotedShipmentEntityId.' ORDER BY '.$quotedShipmentEntityId.')')
                    )
                )
                ->joinLeft(array('track' => $this->getTable('sales/shipment_track')),
                    'track.order_id = main_table.order_id',
                    array('track.track_number', 'track.title')
                )
                ->joinLeft(array('order' => $this->getTable('sales/order')),
                    'order.entity_id = main_table.order_id',
                    array('order.shipping_method')
                )
                ->where('shipment.created_at IS NOT NULL')
                ->group('main_table.order_id');
        }

        return $this;
    }

    /**
     * Add a cancelled sales orders filter to the collection.
     * @return Channelpilotsolutions_Channelpilot_Model_Resource_Order_Collection
     */
    public function addCancelledSalesOrderFilter() {
        $this->getSelect()
            ->joinLeft(array('sales_order' => $this->getTable('sales/order')),
                'sales_order.entity_id = main_table.order_id',
                null
            )
            ->where('sales_order.status = ?', Mage_Sales_Model_Order::STATE_CANCELED);

        return $this;
    }

    /**
     * Add an "order is paid" filter to the collection.
     * @return  Channelpilotsolutions_Channelpilot_Model_Resource_Order_Collection
     */
    public function addIsPaidFilter() {
        $this->getSelect()
            ->joinLeft(array('sales_order' => $this->getTable('sales/order')),
                'main_table.order_id = sales_order.entity_id',
                array('total_due' => new Zend_Db_Expr('(sales_order.grand_total - IFNULL(sales_order.total_paid, 0))'))
            )
            ->where('total_due = 0')
            ->group('main_table.order_id');
        return $this;
    }
}