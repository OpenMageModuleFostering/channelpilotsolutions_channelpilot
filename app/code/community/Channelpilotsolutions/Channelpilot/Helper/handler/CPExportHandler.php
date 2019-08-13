<?php

/**
 * an cp export handler
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPExportHandler extends CPAbstractHandler {

	private $_storeId;

	/**
	 * Handle status event
	 *
	 */
	public function handle() {
        ini_set('max_execution_time', 7200);
        $store = Mage::app()->getRequest()->getParam('store', null);
        try {
            $this->_storeId = Mage::app()->getStore($store)->getId();
        } catch(Exception $e) {
            $this->_handleStoreException();
            return;
        }

        // start the xml output right now to output the exported products as soon as
        // each product has been processed
        header('Content-Type: text/xml; charset=utf-8');
        echo '<?xml version="1.0"?>';
        echo '<root><catalog>';

        $this->_export();

        echo "</catalog></root>";
	}

    /**
     * Display an error message based on current export (and therefore display) method
     * if an exception has occured during Mage::app()->getStore().
     */
    protected function _handleStoreException() {
        // The exception thrown by Mage::app()->getStore() has an empty message ...
        $xml = new SimpleXMLElement('<root></root>');
        $xml->addChild('error', 'Error retrieving store.');
        header('Content-Type: text/xml; charset=utf-8');
        echo $xml->asXML();
        exit();
    }

    /**
     * Callback function used for the collection iterator.
     * The function receives an array containing the fetched row from the database.
     * Saves the product to export in the _productData array.
     * @param $args array
     */
    public function indexedExportCallback($args)
    {
        $row = $args['row'];
        echo $row['product_data'];
    }

    /**
     * Export the products and return them as array.
     */
    protected function _export() {
        $flatEnabled = false;
        if(class_exists('Mage_Core_Model_App_Emulation')) {
            /* @var $flatHelper Mage_Catalog_Helper_Product_Flat */
            $flatHelper = Mage::helper('catalog/product_flat');
            /* @var $emulationModel Mage_Core_Model_App_Emulation */
            $emulationModel = Mage::getModel('core/app_emulation');
            if ($flatHelper) {
                $flatEnabled = method_exists($flatHelper, 'isAvailable') ? $flatHelper->isAvailable() : $flatHelper->isEnabled();
                if($flatEnabled) {
                    // Emulate admin environment to disable using flat model - otherwise we won't get global stats
                    // for all stores
                    $initialEnvironmentInfo = $emulationModel->startEnvironmentEmulation(0, Mage_Core_Model_App_Area::AREA_ADMINHTML);
                }
            }
        }

        /** @var  $exportHelper Channelpilotsolutions_Channelpilot_Helper_Export */
        $exportHelper = Mage::helper('channelpilot/export');
        $exportMethod = Mage::getStoreConfig('channelpilot_export/channelpilot_productfeed/channelpilot_export_method');

        // live export
        if(
            $exportMethod == Channelpilotsolutions_Channelpilot_Model_Adminhtml_Source_Exportmethod::EXPORT_METHOD_LIVE &&
            $exportHelper->getAttributeCount() <= Channelpilotsolutions_Channelpilot_Helper_Export::MAX_ATTRIBUTE_COUNT_FOR_LIVE_EXPORT
        ) {
            $collection = $exportHelper->getProductCollection($this->_storeId);

            // collection could not be loaded
            if($collection === null) {
                return null;
            }

            $chunkSize = $exportHelper->getChunkSize();
            $pages = ceil($collection->getSize() / $chunkSize);

            $collection->setPageSize($chunkSize);

            $backendModel = $exportHelper->getBackendModel();

            for($i = 1; $i <= $pages; $i++) {
                $collection->clear();
                $collection->setCurPage($i);
                foreach($collection as $item) {

                    $backendModel->afterLoad($item);
                    $productData = $exportHelper->getFullProductData($item);

                    $productXml = new SimpleXMLElement('<product></product>');
                    $exportHelper->productToXml($productData, $productXml);
                    $dom = dom_import_simplexml($productXml);
                    echo $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);
                }
            }
        } else { // indexed export
            $collection = Mage::getModel('channelpilot/feedexport_indexer')
                ->getCollection()
                ->addFieldToFilter('store_id', array('eq' => $this->_storeId));

            // output an error in case the collection is empty / no data found for store
            if($collection->getSize() == 0) {
                $xml = new SimpleXMLElement('<error></error>');
                $xml->addChild('message', 'No data found for store '.$this->_storeId.' in index table. Please reindex the data.');
                $dom = dom_import_simplexml($xml);
                echo $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);
                return;
            }

            $iterator = Mage::getSingleton('core/resource_iterator');
            $iterator->walk($collection->getSelect(), array(array($this, 'indexedExportCallback')));
        }

        // stop emulating admin store and set initial environment
        if ($flatEnabled) {
            $emulationModel->stopEnvironmentEmulation($initialEnvironmentInfo);
        }
    }
}

?>
