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
class Channelpilotsolutions_Channelpilot_Block_Adminhtml_Feedexport_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('channelpilot_feed_export');
        $this->setDefaultDir('product_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(false);
    }

    protected function _prepareCollection()
    {
        /** @var  $storeSwitcherBlock Mage_Adminhtml_Block_Store_Switcher */
        $storeSwitcherBlock = $this->getLayout()->getBlock('store_switcher');
        $storeId = $storeSwitcherBlock->getStoreId();
        $collection = Mage::getModel('channelpilot/feedexport_indexer')->getCollection()
            ->addFieldToFilter('store_id', array('eq' => $storeId));

        $this->setCollection($collection);
        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {
        $helper = Mage::helper('channelpilot');

        $this->addColumn('product_id', array(
            'header'        => $helper->__('CP Product ID'),
            'index'         => 'product_id',
            'type'          => 'text',
        ));

        $this->addColumn('sku', array(
            'header'        => $helper->__('CP Product SKU'),
            'index'         => 'sku',
            'type'          => 'text',
        ));

        $this->addColumn('created_at', array(
            'header'        => $helper->__('CP Created At'),
            'index'         => 'created_at',
            'type'          => 'datetime',
            'filter'        => false,
        ));

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/', array('_current' => true));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/view', array(
                'store'=>$this->getRequest()->getParam('store'),
                'product_id'=>$row->getProductId())
        );
    }
}