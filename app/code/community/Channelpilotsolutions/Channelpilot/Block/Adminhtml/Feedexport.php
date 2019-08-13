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
class Channelpilotsolutions_Channelpilot_Block_Adminhtml_Feedexport extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'channelpilot_core';
        $this->_controller = 'adminhtml_feedexport';
        $this->_headerText = Mage::helper('channelpilot')->__('CP Channelpilot Product Feed Export');

        parent::__construct();
        $this->_removeButton('add');

        $helper = Mage::helper('channelpilot');
        $this->_addButton('truncate_index_table', array(
            'label'     => $helper->__('CP Truncate Index Table'),
            'onclick'   => "
                if(confirm('{$helper->__('CP Warning, this will delete all entries and the data has to be reindexed. Proceed ?')}')) setLocation('{$this->getUrl('*/*/truncate')}')
            ",
            'class'     => 'delete'
        ));
    }
}