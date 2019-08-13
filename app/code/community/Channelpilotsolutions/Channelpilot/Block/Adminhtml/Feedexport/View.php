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
class Channelpilotsolutions_Channelpilot_Block_Adminhtml_Feedexport_View extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'product_id';
        $this->_blockGroup = 'channelpilot_core';
        $this->_controller = 'adminhtml_feedexport';
        $this->_mode = 'view';

        $this->_removeButton('save');
        $this->_removeButton('delete');
        $this->_removeButton('reset');
    }

    public function getHeaderText()
    {
        $productId = $this->getRequest()->getParam('product_id');
        $storeId = $this->getRequest()->getParam('store', 1);
        $data = Mage::getResourceModel('channelpilot/feedexport_indexer')->getDataForProductIdAndStoreId($productId, $storeId);
        Mage::register('cp_product_feed_data', $data);
        return Mage::helper('channelpilot')->__('CP Showing feed export data for product %s and store %s - Created at: %s', $productId, $storeId, $data['created_at']);
    }

    public function getBackUrl()
    {
        $storeId = $this->getRequest()->getParam('store');
        return $this->getUrl('*/*/', array('store' => $storeId));
    }
}