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
class Channelpilotsolutions_Channelpilot_Model_Feedexport_Indexer extends Mage_Index_Model_Indexer_Abstract
{
    const EVENT_MATCH_RESULT_KEY               = 'channelpilot_product_feed_export_result';

    const EVENT_KEY_UPDATE_PRODUCT_ID          = 'channelpilot_feed_export_update_product_id';
    const EVENT_KEY_DELETE_PRODUCT_ID          = 'channelpilot_feed_export_delete_product_id';
    const EVENT_KEY_MASS_ACTION_PRODUCT_IDS    = 'channelpilot_feed_export_mass_action_product_ids';

    /**
     * @var array
     */
    protected $_matchedEntities = array(
        Mage_Catalog_Model_Product::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE,
            Mage_Index_Model_Event::TYPE_MASS_ACTION,
        )
    );

    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('channelpilot/feedexport_indexer');
    }

    /**
     * Retrieve Indexer name
     *
     * @return string
     */
    public function getName()
    {
        return Mage::helper('channelpilot')->__('CP Channelpilot Product Feed Export');
    }

    /**
     * Retrieve Indexer description
     *
     * @return string
     */
    public function getDescription()
    {
        return Mage::helper('channelpilot')->__('CP Index product data for the Channelpilot product feed export');
    }

    /**
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        $dataObj = $event->getDataObject();
        switch($event->getType()) {
            case Mage_Index_Model_Event::TYPE_SAVE:
                $event->addNewData(self::EVENT_KEY_UPDATE_PRODUCT_ID, $dataObj->getId());
                break;
            case Mage_Index_Model_Event::TYPE_DELETE:
                $event->addNewData(self::EVENT_KEY_DELETE_PRODUCT_ID, $dataObj->getId());
                break;
            case Mage_Index_Model_Event::TYPE_MASS_ACTION:
                $event->addNewData(self::EVENT_KEY_MASS_ACTION_PRODUCT_IDS, $dataObj->getProductIds());
                break;
        }
    }

    /**
     * @param Mage_Index_Model_Event $event
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();
        if(
            (isset($data[self::EVENT_KEY_UPDATE_PRODUCT_ID]) && !empty($data[self::EVENT_KEY_UPDATE_PRODUCT_ID]))
            || (isset($data[self::EVENT_KEY_DELETE_PRODUCT_ID]) && !empty($data[self::EVENT_KEY_DELETE_PRODUCT_ID]))
            ||(isset($data[self::EVENT_KEY_MASS_ACTION_PRODUCT_IDS]) && !empty($data[self::EVENT_KEY_MASS_ACTION_PRODUCT_IDS]))
        ) {
            $this->callEventHandler($event);
        }
    }

    /**
     * match whether the reindexing should be fired
     * @param Mage_Index_Model_Event $event
     * @return bool
     */
    public function matchEvent(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();
        if (isset($data[self::EVENT_MATCH_RESULT_KEY])) {
            return $data[self::EVENT_MATCH_RESULT_KEY];
        }
        $entity = $event->getEntity();
        $result = true;
        if($entity != Mage_Catalog_Model_Product::ENTITY){
            return;
        }
        $event->addNewData(self::EVENT_MATCH_RESULT_KEY, $result);
        return $result;
    }

    /**
     * Rebuild all index data
     */
    public function reindexAll()
    {
        $this->_getResource()->reindexAll();
    }

    /**
     * Get data for a specific productId and storeId.
     * @param int $productId
     * @param int $storeId
     * @return mixed
     */
    public function getDataForProductIdAndStoreId($productId, $storeId) {
        return $this->_getResource()->getDataForProductIdAndStoreId($productId, $storeId);
    }

    /**
     * Truncate the index table.
     */
    public function truncateIndexTable() {
        $this->_getResource()->truncateIndexTable();
    }
}