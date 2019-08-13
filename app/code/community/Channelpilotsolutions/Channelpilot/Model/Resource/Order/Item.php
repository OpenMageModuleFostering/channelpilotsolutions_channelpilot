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

class Channelpilotsolutions_Channelpilot_Model_Resource_Order_Item extends Mage_Core_Model_Resource_Db_Abstract {

    /**
     * Model Initialization
     *
     */
    protected function _construct() {
        $this->_init('channelpilot/order_item', 'id');
    }

    /**
     * Updates all cancelled items in a single transaction.
     * @param   array       $cpCancellation
     * @throws  Exception
     */
    public function updateCancelledQty(array $cpCancellation) {
        $write = $this->_getWriteAdapter();
        if($write) {
            $write->beginTransaction();
            try {
                foreach($cpCancellation as $cpCancellationItem) {
                    foreach($cpCancellationItem->cancelledItems as $cpOrderItem) {
                        if($cpOrderItem->quantityCancelled > 0) {
                            $ret = $write->update($this->getMainTable(),
                                array('cancelled' => $cpOrderItem->quantityCancelled),
                                sprintf('order_item_id = %s', $cpOrderItem->id)
                            );
                        }
                    }
                }
                $write->commit();
            } catch(Exception $e) {
                $write->rollBack();
                throw $e;
            }
        }
    }
}