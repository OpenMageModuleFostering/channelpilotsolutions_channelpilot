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
 * Class Channelpilotsolutions_Channelpilot_Model_Order_Item
 *
 * @method      int         getId()
 * @method      string      getOrderItemId()
 * @method      string      getMarketplaceOrderItemId()
 * @method      string      getOrderId()
 * @method      int         getCancelled()
 * @method      int         getAmount()
 * @method      int         getAmountDelivered()
 */

class Channelpilotsolutions_Channelpilot_Model_Order_Item extends Mage_Core_Model_Abstract {

    /**
     * Initialize resource model
     */
    protected function _construct() {
        $this->_init('channelpilot/order_item');
    }

    /**
     * Updates all cancelled items in a single transaction.
     * @param   array       $cpCancellation
     * @throws  Exception
     */
    public function updateCancelledQty(array $cpCancellation) {
        $this->getResource()->updateCancelledQty($cpCancellation, $this);
        return $this;
    }
}