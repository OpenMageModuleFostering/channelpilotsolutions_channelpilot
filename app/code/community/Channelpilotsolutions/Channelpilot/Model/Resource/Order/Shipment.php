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
 * @author          Bj�rn Wehner <info@channelpilot.com>
 * @license         <http://www.gnu.org/licenses/> GNU General Public License (GPL 3)
 * @link            http://www.channelpilot.com
 */
class Channelpilotsolutions_Channelpilot_Model_Resource_Order_Shipment extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Model Initialization
     *
     */
    protected function _construct() {
        $this->_init('channelpilot/order_shipment', 'id');
    }

    /**
     * Adds multiple rows to channelpilot/order_shipment.
     * Returns the amount of affected rows or boolean false if no read connection is present.
     * @param   array   $shipments      structure: Array(
     *                                      [0] => Array(
     *                                          'order_id'      => SALES_ORDER_ID,
     *                                          'shipment_id'   => SALES_SHIPMENT_ID
     *                                      )
     *                                  )
     * @return  bool|int
     */
    public function addMultipleShipments(array $shipments) {
        $read = $this->getReadConnection();
        if($read) {
            return $read->insertMultiple($this->getMainTable(), $shipments);
        }

        return false;
    }
}