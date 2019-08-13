<?php

/**
 * an cp export handler
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPExportHandler extends CPAbstractHandler {

	private $_storeId;
	private $_siteId;
	private $_mediaUrl;
	private $_webUrl;
	private $_allCat;
	private $oldCatPath;
	private $_limit;
	private $_last;
    private $_blankProduct;
    private $_exportMethod;
    private $_configurableAttributes = array();
    private $_imageBaseUrl;
    private $_maxAdditionalImages;
    private $_exportFields;
    private $_currencyChange;
    private $_replaceFields;

    const METHOD_XML    = 0;
    const METHOD_JSON   = 1;

	/**
	 * Handle status event
	 *
	 */
	public function handle($exportMethod = self::METHOD_XML) {
        $limit = Mage::app()->getRequest()->getParam('limit', null);
        $store = Mage::app()->getRequest()->getParam('store', null);
        $this->_exportMethod = $exportMethod;
        $this->_limit = $limit;
        $this->_last = Mage::app()->getRequest()->getParam('last', null);
        try {
            $this->_storeId = Mage::app()->getStore($store)->getId();
        } catch(Exception $e) {
            $this->_handleStoreException();
            return;
        }

        $this->initExport();
        $productData = $this->_export();

        switch($this->_exportMethod) {
            case self::METHOD_XML:
                $this->_toXml($productData);
                break;
            case self::METHOD_JSON:
                $this->_toJson($productData);
                break;
            default:
                echo "Export method not supported.";
                return;
        }
	}

    /**
     * Display an error message based on current export (and therefore display) method
     * if an exception has occured during Mage::app()->getStore().
     */
    private function _handleStoreException() {
        // The exception thrown by Mage::app()->getStore() has an empty message ...
        switch($this->_exportMethod) {
            case self::METHOD_XML:
                $xml = new SimpleXMLElement('<root></root>');
                $xml->addChild('error', 'Error retrieving store.');
                header('Content-Type: text/xml; charset=utf-8');
                echo $xml->asXML();
                break;
            case self::METHOD_JSON:
            default:
                $hook = new CPHookResponse();
                $hook->resultCode = CPResultCodes::SYSTEM_ERROR;
                $hook->resultMessage = "Error retrieving store.";
                $hook->writeResponse(self::defaultHeader, json_encode($hook));
        }
    }

    /**
     * Check if another currency (other than the base currency) should be used. Displays an error if the
     * given currency could not be found.
     */
    private function _initCurrencyChange() {
        $this->_currencyChange = null;
        $currencyCode = Mage::app()->getRequest()->getParam('currency', false);
        if ($currencyCode && $currencyCode != '') {
            $result = Mage::getModel('directory/currency')->getCurrencyRates(Mage::app()->getBaseCurrencyCode(), $currencyCode);
            if(count($result) === 0){
                switch($this->_exportMethod) {
                    case self::METHOD_XML:
                        $xml = new SimpleXMLElement('<root></root>');
                        $xml->addChild('error', 'wrong currency');
                        header('Content-Type: text/xml; charset=utf-8');
                        echo $xml->asXML();
                        exit();
                    case self::METHOD_JSON:
                    default:
                        $hook = new CPHookResponse();
                        $hook->resultCode = CPResultCodes::SYSTEM_ERROR;
                        $hook->resultMessage = "wrong currency";
                        $hook->writeResponse(self::defaultHeader, json_encode($hook));
                }
            }
            $this->_currencyChange = $result[$currencyCode];
        }
    }

    /**
     * Initialize the export.
     */
	private function initExport() {
		ini_set('max_execution_time', 7200);

        // Initialize the admin application
        Mage::app('admin');

        $this->_blankProduct = array();
        $this->_blankProduct['entity_id'] = '';
        $this->_blankProduct['sku'] = '';
        $this->_blankProduct['parent_id'] = '';
        $this->_blankProduct['variationTheme'] = '';
        $this->_blankProduct['name'] = '';
        $this->_blankProduct['description'] = '';
        $this->_blankProduct['price'] = '';
        $this->_blankProduct['categories'] = '';
        $this->_blankProduct['manufacturer'] = '';
        $this->_blankProduct['cp_product_url'] = '';
        $this->_blankProduct['cp_image_url'] = '';
        $this->_blankProduct['color'] = '';
        $this->_blankProduct['weight'] = '';
        for($i = 1; $i <= Mage::getStoreConfig('channelpilot_export/channelpilot_productfeed/channelpilot_imagenumber'); $i++) {
            $this->_blankProduct['cp_additional_image_'.$i] = '';
        }

        $specialExportFields = unserialize(Mage::getStoreConfig('channelpilot_export/channelpilot_productfeed/channelpilot_specialexportfields'));
        if(!empty($specialExportFields)) {
            foreach($specialExportFields as $field) {
                if (!empty($field['name'])) {
                    $this->_blankProduct[preg_replace('/\W/', '', $field['name'])] = $field['value'];
                }
            }
        }

		try {
			$store = Mage::app()->getStore($this->_storeId);
			$this->_siteId = $store->getWebsiteId();
			$this->_webUrl = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
			$this->_mediaUrl = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
		} catch (Exception $e) {
			die('Store=' . $this->_storeId . " probably does not exist.");
		}

        $this->buildCategoryTree();

        $this->_initCurrencyChange();

        $this->_initConfigurableAttributes();

        /** @var  $mediaConfig Mage_Catalog_Model_Product_Media_Config */
        $mediaConfig = Mage::getSingleton('catalog/product_media_config');
        $this->_imageBaseUrl = $mediaConfig->getBaseMediaUrl();

        $this->_maxAdditionalImages = Mage::getStoreConfig('channelpilot_export/channelpilot_productfeed/channelpilot_imagenumber');
        $this->_exportFields = unserialize(Mage::getStoreConfig('channelpilot_export/channelpilot_productfeed/channelpilot_exportfields'));
        $this->_replaceFields = unserialize(Mage::getStoreConfig('channelpilot_export/channelpilot_productfeed/channelpilot_replacefields'));
	}

    /**
     * Build the category tree.
     */
    public function buildCategoryTree() {
        $this->_allCat = array();
        $this->oldCatPath = '';

        $categoryCollection = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSort('path');

        foreach($categoryCollection as $category) {
            $path = $this->getCategory($category->getPath());
            if($path !== 0) {
                $this->_allCat[$category->getPath()] = str_replace('Root Catalog', 'Home', $path . '>' . $category->getName());
            } else {
                $this->_allCat[$category->getPath()] = str_replace('Root Catalog', 'Home', $category->getName());
            }
        }
    }

    /**
     * Get the category id from a path.
     * @param   $key
     * @return  int | string
     */
    private function getCategory($key) {
        $return = 0;
        if (strpos($key, '/') != false) {
            $tmpKey = substr($key, 0, strpos($key, strrchr($key, '/')));
            if (isset($this->_allCat[$tmpKey])) {
                $return = $this->_allCat[$tmpKey];
            } else {
                $return = $this->getCategory($tmpKey);
            }
        }
        return $return;
    }

    /**
     * Initialize the configurableAttributes array.
     * Array(
     *  [PRODUCT_ID] => ARRAY(
     *                      [ATTRIBUTE_CODE] => [FRONTEND_LABEL]
     *                  )
     * )
     */
    private function _initConfigurableAttributes() {
        $this->_configurableAttributes = array();

        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');

        $select = $connection->select()
            ->from(array('super_attribute' => Mage::getSingleton('core/resource')->getTableName('catalog/product_super_attribute')), array('attribute_id', 'product_id'))
            ->join(array('attribute' => Mage::getSingleton('core/resource')->getTableName('eav/attribute')),
                'attribute.attribute_id = super_attribute.attribute_id',
                array('attribute_code', 'frontend_label')
            );

        $result = $connection->fetchAll($select);

        foreach($result as $attribute) {
            if(!isset($this->_configurableAttributes[$attribute['product_id']])) {
                $this->_configurableAttributes[$attribute['product_id']] = array();
            }
            $this->_configurableAttributes[$attribute['product_id']][$attribute['attribute_code']] = $attribute['frontend_label'];
        }
    }

    /**
     * Get an array of attribute codes for all configurable attributes of a product ID.
     *
     * @param   int    $productId
     * @return  array
     */
    private function _getConfigurableAttributes($productId) {
        $attributeOptions = array();
        if(isset($this->_configurableAttributes[$productId])) {
            foreach($this->_configurableAttributes[$productId] as $attributeCode => $label) {
                $attributeOptions[] = $label;
            }
        }
        return $attributeOptions;
    }

    /**
     * Export the products and return them as array.
     * @return  array
     */
    private function _export() {
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

        /** @var  $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('*')
            ->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left')
            ->addAttributeToSort('type_id')
            ->setStoreId($this->_storeId);

        // add group price fields
        foreach($this->_exportFields as $field) {
            if(strpos($field['productattribute'], 'group_price') !== false) {
                $groupId = substr($field['productattribute'], 12);
                if(is_numeric($groupId)) {
                    $collection->joinField('group_price_'.$groupId,
                        'catalog/product_index_price',
                        'group_price',
                        'entity_id=entity_id',
                        '{{table}}.customer_group_id='.$groupId,
                        'left');
                }
            }
        }

        if($this->_last) {
            $collection->setCurPage($this->_last);
        }

        if($this->_limit) {
            $collection->setPageSize($this->_limit);
        }

        /** @var  $backendModel  Mage_Catalog_Model_Product_Attribute_Backend_Media */
        $backendModel = $collection->getResource()->getAttribute('media_gallery')->getBackend();

        $onlyStockAndPriceData = (Mage::app()->getRequest()->getParam('priceStock', '') === "true");

        $productData = array();

        /** @var  $item Mage_Catalog_Model_Product */
        foreach ($collection as $item) {

            if($onlyStockAndPriceData) {
                $product = $this->_getOnlyStockAndPriceData($item);
            } else {
                $backendModel->afterLoad($item);
                $product = $this->_getFullProductData($item);
            }

            $productData[] = $product;
        }

        // stop emulating admin store and set initial environment
        if ($flatEnabled) {
            $emulationModel->stopEnvironmentEmulation($initialEnvironmentInfo);
        }

        return $productData;
    }

    /**
     * Get the current $item as array.
     * Returns Array(
     *      [ATTRIBUTE_CODE] => [VALUE]
     * )
     * @param   Mage_Catalog_Model_Product $item
     * @return  array
     */
    private function _getFullProductData(Mage_Catalog_Model_Product $item) {
        $imageUrl = $this->_imageBaseUrl . $item->getImage();

        $isParent = 0;
        if ($item->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $isParent = 1;
        }

        $parentId = null;
        if ($item->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
            $parentId = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($item->getId());
            if(is_array($parentId) && !empty($parentId)) {
                $parentId = $parentId[0];
            }
        }

        $configurableAttributes = array();
        if($parentId && isset($this->_variationThemes[$parentId])) {
            $configurableAttributes = $this->_variationThemes[$parentId];
        } else if($isParent && $parentId === null) {
            $configurableAttributes = $this->_getConfigurableAttributes($item->getId());
            $configurableAttributes = (!empty($configurableAttributes)) ? implode('|', $configurableAttributes) : '';
            $this->_variationThemes[$item->getId()] = $configurableAttributes;
        }

        // workaround... $item->getProductUrl() sometimes adds store code to url (e.g. <url>?___store=default)
        $productUrl = $this->_webUrl . $item->getUrlPath();
        $colorText = $item->getAttributeText('color');
        $rulePrice = Mage::getModel('catalogrule/rule')->calcProductPriceRule($item->setStoreId($this->_storeId),$item->getPrice());
        $price = ($rulePrice) ? $rulePrice : $item->getPrice();

        $product = $this->_blankProduct;

        $product['entity_id'] = $item->getId();
        $product['sku'] = $item->getSku();
        $product['parent_id'] = $parentId;
        $product['variationTheme'] = $configurableAttributes;
        $product['name'] = html_entity_decode($item->getName());
        $product['description'] = html_entity_decode($item->getDescription());

        $product['price'] = $price;
        if($this->_currencyChange) {
            $product['price'] = round($product['price']*$this->_currencyChange, 2);
        }

        $product['categories'] = $this->_getCategoryInformation($item);
        $product['manufacturer'] = html_entity_decode($item->getManufacturer());
        $product['cp_product_url'] = $productUrl;
        $product['cp_image_url'] = $imageUrl;
        $product['color'] = ($colorText) ? html_entity_decode($colorText) : null;
        $product['weight'] = $item->getWeight();

        $counter = 1;
        foreach ($item->getMediaGalleryImages() as $image) {
            // break if the maximum amount of additional images has been reached
            if ($counter > $this->_maxAdditionalImages) {
                break;
            }

            // ignore the base image; it has already been added
            if ($image->getFile() == $item->getImage()) {
                continue;
            }

            $product['cp_additional_image_' . $counter] = $this->_imageBaseUrl . $image->getFile();
            $counter++;
        }

        $product['is_parent'] = $isParent;

        foreach($this->_exportFields as $field) {
            $code = $field['productattribute'];
            if(strpos($code, 'group_price') !== false) {
                $groupId = substr($code, 12);
                $customerGroup = Mage::getModel('customer/group')->load($groupId);
                $groupCode = str_replace(' ', '_', $customerGroup->getCustomerGroupCode());
                $product['group_price_'.$groupCode] = $item->getData('group_price_'.$customerGroup->getId());
            } else {
                switch($code) {
                    case 'qty':
                        $product[$code] = $item->getQty();
                        break;
                    case 'stock_status':
                        $product[$code] = $item->getStockItem()->getIsInStock();
                        break;
                    case 'tax_class_id':
                        $product[$code] = $item->getData($code);
                        break;
                    default:
                        $attributeText = $item->getAttributeText($code);
                        if(is_array($attributeText)) {
                            $attributeText = implode(',',$attributeText);
                        }
                        $product[$code] = ($attributeText) ? html_entity_decode($attributeText) : html_entity_decode($item->getData($code));
                }
            }
        }

        if(!empty($this->_replaceFields) && !$isParent && $parentId !== null) {
            $parent = Mage::getModel('catalog/product')->load($parentId);
            foreach($this->_replaceFields as $field) {
                $code = $field['productattribute'];
                if(strpos($code, 'group_price') !== false) {
                    $groupId = substr($code, 12);
                    $customerGroup = Mage::getModel('customer/group')->load($groupId);
                    $groupPrices = $parent->getData('group_price');
                    foreach($groupPrices as $groupPrice) {
                        if($groupPrice['cust_group'] == $groupId) {
                            $groupCode = str_replace(' ', '_', $customerGroup->getCustomerGroupCode());
                            $product['group_price_'.$groupCode] = $groupPrice['price'];
                        }
                    }
                } else {
                    switch($code) {
                        case 'qty':
                            $product[$code] = $parent->getStockItem->getQty();
                            break;
                        case 'stock_status':
                            $product[$code] = $parent->getStockItem()->getIsInStock();
                            break;
                        case 'tax_class_id':
                            $product[$code] = $parent->getData($code);
                            break;
                        default:
                            $attributeText = $parent->getAttributeText($code);
                            if(is_array($attributeText)) {
                                $attributeText = implode(',',$attributeText);
                            }
                            $product[$code] = ($attributeText) ? html_entity_decode($attributeText) : html_entity_decode($parent->getData($code));
                    }
                }
            }
        }

        return $product;
    }

    /**
     * Get only the stock and price data for a product as array. Attributes: entity_id, sku, price, qty.
     * Array(
     *      [ATTRIBUTE_CODE] => [VALUE]
     * )
     * @param   Mage_Catalog_Model_Product $item
     * @return  array
     */
    private function _getOnlyStockAndPriceData(Mage_Catalog_Model_Product $item) {
        $rulePrice = Mage::getModel('catalogrule/rule')->calcProductPriceRule($item->setStoreId($this->_storeId),$item->getPrice());
        $price = ($rulePrice) ? $rulePrice : $item->getPrice();

        $product['entity_id'] = $item->getId();
        $product['sku'] = $item->getSku();
        $product['price'] = $price;
        if($this->_currencyChange) {
            $product['price'] = round($product['price']*$this->_currencyChange, 2);
        }

        $product['qty'] = $item->getQty();

        return $product;
    }

    /**
     * Creates and shows an XML based on the values of the $productData array created by the export method.
     * Sends a Content-Type header.
     * @param   array $productData
     */
    private function _toXml(array $productData) {
        $xml = new SimpleXMLElement('<root></root>');
        $xmlCatalog = $xml->addChild('catalog');
        foreach($productData as $product) {
            $xmlProduct = $xmlCatalog->addChild('product');
            $this->_productToXml($product, $xmlProduct);
        }
        header('Content-Type: text/xml; charset=utf-8');
        echo $xml->asXML();
        exit();
    }

    /**
     * Adds the values of the $product array to the $xml structure.
     * @param   array               $product
     * @param   SimpleXMLElement    $xml
     */
    private function _productToXml(array $product, SimpleXMLElement $xml) {
        foreach($product as $code => $value) {
            if(is_array($value)) {
                $node = $xml->addChild($code);
                $this->_productToXml($value, $node);
            } else if (is_string($value) || is_numeric($value) || is_bool($value) || is_null($value)) {
                $xml->addChild($code, htmlspecialchars($value));
            }
        }
    }

    /**
     * Shows the content of $productData as JSON.
     * @param   array   $productData
     */
    private function _toJson(array $productData) {
        $hook = new CPHookResponse();
        $hook->resultCode = CPResultCodes::SUCCESS;
        $hook->products = $productData;
        $hook->resultMessage = "ProductData of " . sizeof($hook->products) . " articles";
        $hook->moreAvailable = true;
        if (sizeof($hook->products) < $this->_limit) {
            $hook->moreAvailable = false;
        }
        $hook->writeResponse(self::defaultHeader, json_encode($hook));
    }

    /**
     * Get the category information for a product.
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    private function _getCategoryInformation(Mage_Catalog_Model_Product $product) {
        $categorieField = '';
        $this->oldCatPath = '';

        /** @var  $category Mage_Catalog_Model_Category */
        foreach($product->getCategoryCollection() as $category) {
            if (($this->oldCatPath == '') || (strpos($category->getPath(), $this->oldCatPath) != 0)) {
                // Start tree
                if ($this->oldCatPath != '') {
                    if ($categorieField != '') {
                        $categorieField = $categorieField . ', ' . $this->_allCat[$this->oldCatPath];
                    } else {
                        $categorieField = $this->_allCat[$this->oldCatPath];
                    }
                }
                $this->oldCatPath = $category->getPath();
            } else {
                // Add to tree
                $this->oldCatPath = $category->getPath();
            }

        }

        if ($categorieField != '') {
            $categorieField = $categorieField . ', ' . $this->_allCat[$this->oldCatPath];
        } else {
            if ($this->oldCatPath != '') {
                $categorieField = $this->_allCat[$this->oldCatPath];
            }
        }

        return ltrim($categorieField, '>');
    }
}

?>
