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
class Channelpilotsolutions_Channelpilot_Model_Resource_Feedexport_Indexer extends Mage_Index_Model_Resource_Abstract
{
    /**
     * Define main index table
     *
     */
    protected function _construct()
    {
        $this->_init('channelpilot/product_feed_export_index', 'product_id');
    }

    /**
     * @param array $productIds     the product ids that should be reindexed
     * @param array $storeIds       the store ids that should be used for reindexing the product data
     * @return Channelpilotsolutions_Channelpilot_Model_Resource_Feedexport_Indexer
     * @throws Exception
     */
    protected function _reindexProductIds($productIds, $storeIds) {
        if(empty($productIds)) {
            $productIds = array();
        }

        if(!is_array($productIds)) {
            $productIds = array($productIds);
        }

        if(!is_array($storeIds)) {
            $storeIds = array($storeIds);
        }

        $adapter = $this->_getWriteAdapter();

        try {
            /** @var  $export Channelpilotsolutions_Channelpilot_Model_Export */
            $export = Mage::helper('channelpilot/export');
            $chunkSize = $export->getChunkSize();
            foreach($storeIds as $storeId) {

                $collection = $export->getProductCollection($storeId, $productIds);
                if($collection === null) {
                    continue;
                }
                $pages = ceil($collection->getSize() / $chunkSize);

                $collection->setPageSize($chunkSize);

                for($i = 1; $i <= $pages; $i++) {
                    $adapter->beginTransaction();

                    $collection->clear();
                    $collection->setCurPage($i);
                    foreach($collection as $item) {
                        $where = array();
                        $where[] = $adapter->quoteInto('product_id = ?', $item->getId());
                        $where[] = $adapter->quoteInto('store_id = ?', $storeId);
                        $adapter->delete($this->getMainTable(), $where);

                        $bind = array();

                        $productData = $export->getFullProductData($item);

                        $productXml = new SimpleXMLElement('<product></product>');
                        $export->productToXml($productData, $productXml);
                        $dom = dom_import_simplexml($productXml);

                        $bind['product_id'] = $item->getId();
                        $bind['store_id'] = $storeId;
                        $bind['sku'] = $item->getData('sku');
                        $bind['created_at'] = now();
                        $bind['product_data'] = $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);
                        $adapter->insert($this->getMainTable(), $bind);
                    }

                    $adapter->commit();
                }
            }
        } catch (Exception $e) {
            $adapter->rollBack();
            throw $e;
        }

        return $this;
    }

    /**
     * @return Channelpilotsolutions_Channelpilot_Model_Resource_Feedexport_Indexer
     * @throws Exception
     */
    public function reindexAll() {
        $storeIds = Mage::helper('channelpilot')->getAllStoreIds();
        $this->_reindexProductIds(null, $storeIds);
        return $this;
    }

    /**
     * Process produce delete
     * If the deleted product was found in a composite product(s) update it
     *
     * @param Mage_Index_Model_Event $event
     * @return Channelpilotsolutions_Channelpilot_Model_Resource_Feedexport_Indexer
     */
    public function catalogProductDelete(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();
        if(!isset($data[Channelpilotsolutions_Channelpilot_Model_Feedexport_Indexer::EVENT_KEY_UPDATE_PRODUCT_ID])) {
            return $this;
        }
        /** @var  $dataObject  Mage_Catalog_Model_Product */
        $dataObject = $event->getDataObject();
        $storeIds = $dataObject->getStoreId();
        if($storeIds == 0) {
            $storeIds = $event->getDataObject()->getStoreIds();
        }
        $this->_reindexProductIds($data[Channelpilotsolutions_Channelpilot_Model_Feedexport_Indexer::EVENT_KEY_UPDATE_PRODUCT_ID], $storeIds);

        return $this;
    }

    /**
     * Process product save.
     * Method is responsible for index support
     * when product was saved and changed attribute(s) has an effect on price.
     *
     * @param Mage_Index_Model_Event $event
     * @return Channelpilotsolutions_Channelpilot_Model_Resource_Feedexport_Indexer
     */
    public function catalogProductSave(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();
        if(!isset($data[Channelpilotsolutions_Channelpilot_Model_Feedexport_Indexer::EVENT_KEY_UPDATE_PRODUCT_ID])) {
            return $this;
        }
        /** @var  $dataObject  Mage_Catalog_Model_Product */
        $dataObject = $event->getDataObject();
        $storeIds = $dataObject->getStoreId();
        if($storeIds == 0) {
            $storeIds = $event->getDataObject()->getStoreIds();
        }
        $this->_reindexProductIds($data[Channelpilotsolutions_Channelpilot_Model_Feedexport_Indexer::EVENT_KEY_UPDATE_PRODUCT_ID], $storeIds);

        return $this;
    }

    /**
     * Process product mass update action
     *
     * @param Mage_Index_Model_Event $event
     * @return Channelpilotsolutions_Channelpilot_Model_Resource_Feedexport_Indexer
     */
    public function catalogProductMassAction(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();
        if(!isset($data[Channelpilotsolutions_Channelpilot_Model_Feedexport_Indexer::EVENT_KEY_MASS_ACTION_PRODUCT_IDS])) {
            return $this;
        }

        $storeIds = Mage::helper('channelpilot')->getAllStoreIds();
        $this->_reindexProductIds($data[Channelpilotsolutions_Channelpilot_Model_Feedexport_Indexer::EVENT_KEY_MASS_ACTION_PRODUCT_IDS], $storeIds);

        return $this;
    }

    /**
     * Load the entry for a specific productId and storeId.
     * @param int $productId
     * @param int $storeId
     * @return array
     */
    public function getDataForProductIdAndStoreId($productId, $storeId) {
        $read = $this->getReadConnection();
        if($read) {
            $select = $read->select()
                ->from($this->getTable('channelpilot/product_feed_export_index'))
                ->where($read->quoteInto('product_id = ?', $productId))
                ->where($read->quoteInto('store_id = ?', $storeId));

            return $read->fetchRow($select);
        }
    }

    /**
     * Truncate the index table.
     */
    public function truncateIndexTable() {
        $write = $this->_getWriteAdapter();

        if($write) {
            $write->truncateTable($this->getMainTable());
        }
    }
}