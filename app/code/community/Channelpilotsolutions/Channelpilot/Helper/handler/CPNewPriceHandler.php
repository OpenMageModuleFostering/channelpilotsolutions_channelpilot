<?php

/**
 * an cp delivery handler
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPNewPriceHandler extends CPAbstractHandler {

	/**
	 * Handle delivery hook.
	 * @return type
	 */
	public function handle() {
        $token = Mage::app()->getRequest()->getParam('token', false);
        $methodParam = Mage::app()->getRequest()->getParam('method', false);
        $priceId = Mage::app()->getRequest()->getParam('priceId', false);
		if ($token && self::isIpAllowedViaSecurityToken($token)) {
			if ($priceId) {
				$merchantId = self::getMerchantId($token);

				$filterFrom = self::getLastPriceUpdate($priceId);
				$method = "all";
				if (isset($filterFrom)) {
					$method = "update";
				}
				$filterArticles = null;

				$api = new ChannelPilotSellerAPI_v1_0($merchantId, $token);
				$result = $api->getDynamicArticlePrices($priceId, $method, $filterArticles, $filterFrom);
				$unknownArticles = array();
				$lastPriceUpdate = null;

				if (isset($result->managedArticlePrices)) {
                    $lastPriceUpdate = $this->_manageArticlePrices($result, $token, $unknownArticles);
				}

				if (isset($lastPriceUpdate)) {
                    $cpPrices = Mage::getModel('channelpilot/prices')->load($priceId);
                    $cpPrices->setData('last_price_update', $lastPriceUpdate);
					try {
                        $cpPrices->save();
					} catch (Exception $e) {
						CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, "Exception during set last_price_update: " . $e->getMessage(), " Exception during set last_price_update: " . $e->getMessage());
					}
				}

				self::hookResult($result->moreAvailable, $unknownArticles);

                /**
                 *  reindex prices
                 *
                 *	1 = Product Attributes
                 *	2 = Product prices
                 *	3 = Catalog URL Rewrites
                 *	4 = Product Flat Data
                 *	5 = Category Flat Data
                 *	6 = Category Products
                 *	7 = Catalog Search Index
                 *	8 = Stock Status
                 *	9 = Tag Aggregation Data
                 */
                Mage::getModel('index/process')->load(2)->reindexEverything();
                exit();
			} else {
				CPErrorHandler::handle(CPErrors::RESULT_MISSING_PARAMS, "no priceId set for method: " . $methodParam, "no priceId set for method: " . $methodParam);
			}
		} else {
			if (empty($token)) {
				CPErrorHandler::handle(CPErrors::RESULT_MISSING_PARAMS, "no token found", "no token found");
			} else {
				CPErrorHandler::handle(CPErrors::RESULT_FAILED, "ip not allowed by token: " . $token, "ip not allowed by token: " . $token);
			}
		}
	}

    /**
     * @param   mixed       $result
     * @param   string      $token
     * @param   array       $unknownArticles
     * @return  mixed
     */
    protected function _manageArticlePrices($result, $token, &$unknownArticles) {
        $lastPriceUpdate = null;

        $shopId = self::getShopId($token);
        $store = Mage::getSingleton('core/store')->load($shopId);

        $useNet = false;
        if (Mage::getStoreConfig('channelpilot_pricecontrol/channelpilot_general/channelpilot_saveGrossOrNetPrices') === "net") {
            $useNet = true;
        }
        $taxCalculation = Mage::getModel('tax/calculation');
        $taxRateRequest = $taxCalculation->getRateRequest(null, null, null, $store);
        $taxRates = array();

        $priceField = Mage::getStoreConfig('channelpilot_pricecontrol/general_prices/channelpilot_generalPriceField');
        $idField = Mage::getStoreConfig('channelpilot_general/channelpilot_general/channelpilot_articlenumber');

        if(!in_array($priceField, array('price', 'special_price'))) {
            CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, " Error by choosing price field '$priceField'", "Error by choosing price field '$priceField'");
        }

        $resource = Mage::getModel('catalog/product')->getResource();

        // in case the prices are net prices, store the tax rate for every product
        if($useNet) {
            $productIds = array();

            // get every used product entity_id
            foreach ($result->managedArticlePrices as $articlePrice) {
                $id = $articlePrice->article->id;
                if($idField == 'sku') {
                    $id = Mage::getModel('catalog/product')->getIdBySku($articlePrice->article->id);
                }
                $productIds[] = $id;
            }

            // create a collection selecting the fields tax_class_id, entity_id and sku
            // for all products from $result->managedArticlePrices
            $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect(array('tax_class_id', 'entity_id', 'sku'))
                ->addFieldToFilter($idField, array('in' => $productIds));


            // store the tax rate for every product
            /** @var  $product Mage_Catalog_Model_Product */
            foreach($collection as $product) {
                $taxRates[$product->getId()] = $taxCalculation->getRate($taxRateRequest->setProductClassId($product->getTaxClassId()));
            }
        }

        foreach ($result->managedArticlePrices as $articlePrice) {
            $id = $articlePrice->article->id;
            if($idField == 'sku') {
                $id = Mage::getModel('catalog/product')->getIdBySku($articlePrice->article->id);
            }

            $product = Mage::getModel('catalog/product')
                ->unsetData()
                ->setId($id)
                ->setStoreId($shopId);

            $price = $useNet ? $articlePrice->price / (($taxRates[$id] / 100) + 1) : $articlePrice->price;
            switch ($priceField) {
                case 'price':
                    $product->setPrice($price);
                    $resource->saveAttribute($product, 'price');
                    break;
                case 'special_price':
                    $product->setSpecialPrice($price);
                    $resource->saveAttribute($product, 'special_price');
                    break;
                default:
                    CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, " Error by choosing price field '$priceField'", "Error by choosing price field '$priceField'");
                    break;
            }
            $lastPriceUpdate = $articlePrice->lastUpdate;
        }

        return $lastPriceUpdate;
    }

    protected function hookResult($moreAvailable, $errorArticles = null) {
		$hook = new CPHookResponse();
		$hook->resultCode = CPResultCodes::SUCCESS;
		$hook->resultMessage = "NEW PRICE HOOK SUCCESS";
		$hook->moreAvailable = $moreAvailable;
		if (empty($errorArticles) || sizeof($errorArticles) == 0) {
			$hook->unknownArticles = null;
		} else {
			$hook->unknownArticles = $errorArticles;
		}

        // Turn off output buffering
        ini_set('output_buffering', 'off');
        // Turn off PHP output compression
        ini_set('zlib.output_compression', false);

        // Implicitly flush the buffer(s)
        ini_set('implicit_flush', true);
        ob_implicit_flush(true);

        header(self::defaultHeader);
        print_r(json_encode($hook));

        ob_flush();
	}

    protected function getLastPriceUpdate($priceId) {
        $cpPrices = Mage::getModel('channelpilot/prices')->load($priceId);
		try {
			if ($cpPrices && $cpPrices->getId()) {
                $date = new DateTime($cpPrices->getLastPriceUpdate());
				return date_format($date, 'Y-m-d') . "T" . date_format($date, 'H:i:s');
			} else {
                $cpPrices->unsetData()
                    ->setPriceId($priceId);
				try {
                    $cpPrices->save();
					return null;
				} catch (Exception $e) {
					CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, "Exception during set last_price_update: " . $e->getMessage(), " Exception during set last_price_update: " . $e->getMessage());
				}
			}
		} catch (Exception $e) {
			CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, "Exception in getLastPriceUpdate(): " . $e->getMessage(), "Exception in getLastPriceUpdate(): " . $e->getMessage());
		}
	}

}

?>
