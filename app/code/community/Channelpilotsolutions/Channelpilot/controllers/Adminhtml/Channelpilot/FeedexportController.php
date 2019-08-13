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
class Channelpilotsolutions_Channelpilot_Adminhtml_Channelpilot_FeedexportController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function viewAction(){
        $storeId = $this->getRequest()->getParam('store', false);
        if($storeId === false) {
            Mage::getSingleton('adminhtml/session')->addNotice(
                Mage::helper('channelpilot')->__('CP Please specify store.')
            );
            $this->_redirect('*/*/');
            return;
        }
        $this->loadLayout();
        $this->renderLayout();
    }

    public function truncateAction() {
        $helper = Mage::helper('channelpilot');

        Mage::getModel('channelpilot/feedexport_indexer')->truncateIndexTable();

        /** @var  $process Mage_Index_Model_Indexer */
        $process = Mage::getSingleton('index/indexer')->getProcessByCode('channelpilot_feed_export');
        $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);

        Mage::getSingleton('adminhtml/session')->addSuccess($helper->__('CP The data has been deleted. Please reindex the export data.'));
        $this->_redirect('*/*/');
    }
}