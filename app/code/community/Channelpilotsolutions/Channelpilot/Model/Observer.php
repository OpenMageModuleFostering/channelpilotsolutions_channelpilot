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
class Channelpilotsolutions_Channelpilot_Model_Observer
{
    /**
     * @param Varien_Event_Observer $observer
     */
    public function modelConfigDataSaveBefore(Varien_Event_Observer $observer) {
        $event = $observer->getEvent();
        $config = $event->getObject();
        $section = $config->getSection();
        if(strtolower($section) == 'channelpilot_export') {
            $groups = $config->getGroups();
            $groups = $this->_checkDataFields($groups);
            $config->setGroups($groups);
            $this->_checkProductUrlGenerationMethod($groups);
        }
    }

    /**
     * Change the status of the product feed export to require reindex.
     */
    protected function _changeStatusToRequireReindex() {
        // set status to require reindex for the product export feed index
        /** @var  $process Mage_Index_Model_Indexer */
        $process = Mage::getSingleton('index/indexer')->getProcessByCode('channelpilot_feed_export');
        $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
    }

    /**
     * Checks if the data fields have changed. In that case set the index status to "require reindex" for the
     * product feed export index.
     * It will also create a (session) message for the user. This notice will be shown if the user
     * selected too many data fields if the export method is set to "live" and will tell the user that the
     * export will now use the indexed method and that the product feed export index needs to be kept up to date.
     * @param array $groups
     */
    protected function _checkDataFields($groups) {
        if(isset($groups['channelpilot_productfeed']['fields']['channelpilot_exportfields']['value'])) {
            $currentDataFields = unserialize(Mage::getStoreConfig('channelpilot_export/channelpilot_productfeed/channelpilot_exportfields'));
            $dataFields = $groups['channelpilot_productfeed']['fields']['channelpilot_exportfields']['value'];
            if(isset($dataFields['__empty'])) {
                unset($dataFields['__empty']);
            }

            // check if the selected data fields have changed
            if(count($dataFields) != count($currentDataFields)) {
                $this->_changeStatusToRequireReindex();

                // add a notice if the export method is set to live and there are too many datafields selected
                if(
                    $groups['channelpilot_productfeed']['fields']['channelpilot_export_method']['value'] == Channelpilotsolutions_Channelpilot_Model_Adminhtml_Source_Exportmethod::EXPORT_METHOD_LIVE
                    && count($dataFields) > Channelpilotsolutions_Channelpilot_Helper_Export::MAX_ATTRIBUTE_COUNT_FOR_LIVE_EXPORT
                ) {
                    Mage::getSingleton('adminhtml/session')->addNotice(
                        Mage::helper('channelpilot')->__("CP You have added more than %s data fields for the product feed export. Please note that with this amount the 'indexed' export method will re used regardless of the selected one. Keep in mind that you have to keep the ChannelPilot Product Feed Export index up to date.", Channelpilotsolutions_Channelpilot_Helper_Export::MAX_ATTRIBUTE_COUNT_FOR_LIVE_EXPORT)
                    );
                    $groups['channelpilot_productfeed']['fields']['channelpilot_export_method']['value'] = Channelpilotsolutions_Channelpilot_Model_Adminhtml_Source_Exportmethod::EXPORT_METHOD_INDEXED;
                }
            }
        }
        return $groups;
    }

    /**
     * Checks if the method to generate the product url has been changed. In this case
     * set the indexer process status to require reindex if the export method is set to indexed.
     * @param array $groups
     */
    protected function _checkProductUrlGenerationMethod($groups) {
        $currentProductUrlGenerationMethod = Mage::getStoreConfig('channelpilot_export/channelpilot_productfeed/product_url_generation_method');
        if(isset($groups['channelpilot_productfeed']['fields']['product_url_generation_method']['value'])) {
            $method = $groups['channelpilot_productfeed']['fields']['product_url_generation_method']['value'];
            if($method != $currentProductUrlGenerationMethod) {
                $this->_changeStatusToRequireReindex();
            }
        }
    }
}