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
    private function _manageArticlePrices($result, $token, &$unknownArticles) {
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
        foreach ($result->managedArticlePrices as $articlePrice) {
            $id = $articlePrice->article->id;
            $articleNumber = Mage::getStoreConfig('channelpilot_general/channelpilot_general/channelpilot_articlenumber');
            $product = null;
            switch ($articleNumber) {
                case 'product_id':
                    $product = Mage::getSingleton('catalog/product')->load($id);
                    break;
                case 'sku':
                    $product = Mage::getSingleton('catalog/product')->loadByAttribute('sku', $id);
                    break;
                default:
                    CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, "Error by choosing article number '$articleNumber'", "Error by choosing article number '$articleNumber'");
                    break;
            }
            if (empty($product)) {
                $unknownArticles[] = $id;
            } else {
                if ($useNet && empty($taxRates[$product->getTaxClassId()])) {
                    $taxRates[$product->getTaxClassId()] = $taxCalculation->getRate($taxRateRequest->setProductClassId($product->getTaxClassId()));
                }
                $price = $useNet ? $articlePrice->price / (($taxRates[$product->getTaxClassId()] / 100) + 1) : $articlePrice->price;
                $field = Mage::getStoreConfig('channelpilot_pricecontrol/general_prices/channelpilot_generalPriceField');
                switch ($field) {
                    case 'price':
                        Mage::getSingleton('catalog/product_action')->updateAttributes(array($product->entityId), array('price' => round($price, 4)), $shopId);
                        break;
                    case 'special_price':
                        Mage::getSingleton('catalog/product_action')->updateAttributes(array($product->entityId), array('special_price' => round($price, 4)), $shopId);
                        break;
                    default:
                        CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, " Error by choosing price field '$field'", "Error by choosing price field '$field'");
                        break;
                }
            }
            $lastPriceUpdate = $articlePrice->lastUpdate;
        }


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

        return $lastPriceUpdate;
    }

	private function hookResult($moreAvailable, $errorArticles = null) {
		$hook = new CPHookResponse();
		$hook->resultCode = CPResultCodes::SUCCESS;
		$hook->resultMessage = "NEW PRICE HOOK SUCCESS";
		$hook->moreAvailable = $moreAvailable;
		if (empty($errorArticles) || sizeof($errorArticles) == 0) {
			$hook->unknownArticles = null;
		} else {
			$hook->unknownArticles = $errorArticles;
		}
		$hook->writeResponse(self::defaultHeader, json_encode($hook));
	}

	private function getLastPriceUpdate($priceId) {
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
