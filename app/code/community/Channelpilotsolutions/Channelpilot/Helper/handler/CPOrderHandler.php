<?php

/**
 * an cp order handler
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPOrderHandler extends CPAbstractHandler {

	var $shopId;
	var $store;
	var $websiteId;
	var $ordersImported;

	/**
	 * Handle order event
	 */
	public function handle() {
        $token = Mage::app()->getRequest()->getParam('token', false);
		$this->ordersImported = array();
		if ($token && self::isIpAllowedViaSecurityToken($token)) {
			self::checkConfig();
			$merchantId = self::getMerchantId($token);
			try {
				$this->shopId = self::getShopId($token);
				$this->store = Mage::getModel('core/store')->load($this->shopId);
				$this->websiteId = $this->store->getWebsiteId();
				$oldOrders = self::getOrdersFromDb();
				ini_set('allow_url_fopen', 'On');
				$api = new ChannelPilotSellerAPI_v1_0($merchantId, $token);
				$result = $api->getNewMarketplaceOrders();
				//	Check ResultCode of getNewMarketplaceOrders Result
				if ($result->header->resultCode == CPResultCodes::SUCCESS) {
					$moreAvailable = (bool) $result->moreAvailable;
					$orders = self::importOrders($result->orders);
					foreach ($oldOrders as $oldOrder) {
						if (isset($oldOrder) && !in_array($oldOrder->orderHeader->orderId, $this->ordersImported)) {
							$orders[] = $oldOrder;
						}
					}

					if (sizeof($orders) == 0) {
						self::hookResult(false);
					}
					$result = $api->setImportedOrders($orders, true);
					//	Check ResultCode of setImportedOrders Result
					if ($result->header->resultCode == CPResultCodes::SUCCESS) {
						self::changeStatusOrders($result->updateResults);
						self::hookResult($moreAvailable);
					} else {
						//	Result from getNewMarketplaceOrders has no success
						CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, "request setImportedOrders() resultCode " . $result->header->resultCode, "request setImportedOrders() resultCode " . $result->header->resultCode);
					}
				} else {
					//	Result from getNewMarketplaceOrders has no success
					CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, "request getNewMarketplaceOrders() resultCode " . $result->header->resultCode, "request getNewMarketplaceOrders() resultCode " . $result->header->resultCode);
				}
			} catch (Exception $e) {
				CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, "caught Exception in getNewMarketplaceOrders(): " . $e->getMessage(), "caught Exception in getNewMarketplaceOrders(): " . $e->getMessage() . "\n" . $e->getTraceAsString());
			}
		} else {
			if (empty($token)) {
				CPErrorHandler::handle(CPErrors::RESULT_MISSING_PARAMS, "no token found", "no token found");
			} else {
				CPErrorHandler::handle(CPErrors::RESULT_FAILED, "ip not allowed by token: " . $_GET['token'], "ip not allowed by token: " . $_GET['token']);
			}
		}
	}

	private function hookResult($moreAvailable) {
		$hook = new CPHookResponse();
		$hook->resultCode = CPResultCodes::SUCCESS;
		$hook->resultMessage = "ORDERS HOOK SUCCESS";
		$hook->moreAvailable = $moreAvailable;
		$hook->writeResponse(self::defaultHeader, json_encode($hook));
	}

	private function importOrders($apiOrders) {
		$orders = array();
		foreach ($apiOrders as $apiOrder) {
            $apiOrder = $this->_cleanOrderOfMultipleRowsOfSameItem($apiOrder);
			$orders[] = self::importOrder($apiOrder);
		}
		return $orders;
	}

    private function _getQuote($apiOrder) {
        try {
            $quote = Mage::getModel('sales/quote')->setStoreId($this->shopId);
            $customer = self::getCustomer($apiOrder);
            $quote->assignCustomer($customer);

            foreach ($apiOrder->itemsOrdered as $orderItem) {
                $product = $this->getProduct($orderItem->article->id);
                if ($product == null) {
                    CPErrorHandler::logError("NO ARTICLE FOR IDENTIFIER: " . $orderItem->article->id);
                    $apiOrder->orderHeader->status->hasError = true;
                    $apiOrder->orderHeader->status->errorMessage = "Unknown article: " . $orderItem->article->id;
                    $apiOrder->orderHeader->status->errorCode = CPResultCodes::SHOP_ERROR_ARTICLE_UNKNOWN;
                    return false;
                }
                $quote->addProduct($product, (int) $orderItem->quantityOrdered);
            }

            $quote->getBillingAddress()->importCustomerAddress(Mage::getModel('customer/address')->load($customer->getDefaultBilling()));

            $shippingAddress = $quote->getShippingAddress()->importCustomerAddress(Mage::getModel('customer/address')->load($customer->getDefaultShipping()));
            if (substr(Mage::getVersion(), 2, 3) >= 9) {
                $quote->getBillingAddress()->setCompany($apiOrder->addressInvoice->company);
                $shippingAddress->setCompany($apiOrder->addressDelivery->company);
            }

            $shippingAddress
                ->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod($apiOrder->shipping->typeId)
                ->setPaymentMethod($apiOrder->payment->typeId);
            $quote->setShippingAddress($shippingAddress);


            if (strpos($apiOrder->payment->typeId, 'cp_mp') === false) {
                $quote->getPayment()->importData(array('method' => $apiOrder->payment->typeId));
            } else {
                $quote->getPayment()->importData(array('method' => 'cp_mp',
                        'cc_type' => $apiOrder->payment->typeId
                    )
                );
            }

            $quote->collectTotals()->save();

            return $quote;
        } catch(Exception $e) {
            CPErrorHandler::logError("Exception during _getQuote: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $apiOrder->orderHeader->status->hasError = true;
            $apiOrder->orderHeader->status->errorMessage = "Exception during _getQuote: " . $e->getMessage();
            $apiOrder->orderHeader->status->errorCode = CPResultCodes::SYSTEM_ERROR;
            return false;
        }
    }

    /**
     * Checks if an item has been ordered multiple times and this items uses more than one
     * row. In this case the additional rows will be removed and the quantity and totals will
     * be added to the first row. The order totals will be changed accordingly.
     * @param   object  $apiOrder
     * @return  object
     */
    private function _cleanOrderOfMultipleRowsOfSameItem($apiOrder) {
        $orderItems = array();

        foreach ($apiOrder->itemsOrdered as $orderItem) {
            // check if an item uses more than one row
            if(array_key_exists($orderItem->article->id, $orderItems)) {
                // add the additonal row to the first one
                $orderItems[$orderItem->article->id]->quantityOrdered += $orderItem->quantityOrdered;
                $orderItems[$orderItem->article->id]->costsTotal->net = $orderItems[$orderItem->article->id]->costsSingle->net * $orderItems[$orderItem->article->id]->quantityOrdered;
                $orderItems[$orderItem->article->id]->costsTotal->gross = $orderItems[$orderItem->article->id]->costsSingle->gross * $orderItems[$orderItem->article->id]->quantityOrdered;
                $orderItems[$orderItem->article->id]->costsTotal->tax = $orderItems[$orderItem->article->id]->costsSingle->tax * $orderItems[$orderItem->article->id]->quantityOrdered;

                // calculate the totals for the current orderItem
                $costsNet = $orderItem->quantityOrdered * $orderItem->costsSingle->net;
                $costsGross = $orderItem->quantityOrdered * $orderItem->costsSingle->gross;
                $costsTax = $orderItem->quantityOrdered * $orderItem->costsSingle->tax;

                // add the calculated totals to the item summary
                $apiOrder->summary->totalSumItems->net = $apiOrder->summary->totalSumItems->net + $costsNet;
                $apiOrder->summary->totalSumItems->gross = $apiOrder->summary->totalSumItems->gross + $costsGross;
                $apiOrder->summary->totalSumItems->tax = $apiOrder->summary->totalSumItems->tax + $costsTax;

                // add the calculated totals to the order summary
                $apiOrder->summary->totalSumOrder->net = $apiOrder->summary->totalSumOrder->net + $costsNet;
                $apiOrder->summary->totalSumOrder->gross = $apiOrder->summary->totalSumOrder->gross + $costsGross;
                $apiOrder->summary->totalSumOrder->tax = $apiOrder->summary->totalSumOrder->tax + $costsTax;
            } else {
                $orderItems[$orderItem->article->id] = $orderItem;
            }
        }

        // save the cleaned order items
        $apiOrder->itemsOrdered = $orderItems;

        return $apiOrder;
    }

	private function importOrder($apiOrder) {
		$orderId = self::getOrderId($apiOrder->orderHeader->orderIdExternal, $apiOrder->orderHeader->source);
		if (!empty($orderId)) {
			$order = Mage::getModel('sales/order')->load($orderId);
			$apiOrder->orderHeader->orderId = $order->getIncrementId();
			$apiOrder = self::getOrderItems($apiOrder);
			$this->ordersImported[] = $order->getIncrementId();
			return $apiOrder;
		}
		try {
            $quote = $this->_getQuote($apiOrder);
            if($quote === false) {
                return $apiOrder;
            }

            // disable any discount to be applied and recollect totals
            // has to be done after the quote has been created
            $quote->setTotalsCollectedFlag(false);
            foreach($quote->getAllItems() as $item) {
                $item->setNoDiscount(1);
            }

            $quote->collectTotals()->save();

			$service = Mage::getModel('sales/service_quote', $quote);
			$service->submitAll();
			$order = $service->getOrder();
			$apiOrder->orderHeader->orderId = $order->getIncrementId();

			try {
                Mage::getModel('channelpilot/order')
                    ->unsetData()
                    ->setData(array(
                        'order_id'              => $order->getId(),
                        'order_nr'              => $order->getIncrementId(),
                        'marketplace_order_id'  => $apiOrder->orderHeader->orderIdExternal,
                        'marketplace'           => $apiOrder->orderHeader->source,
                        'shop'                  => $this->shopId,
                        'status'                => $apiOrder->orderHeader->status->identifier
                    ))
                    ->save();
			} catch (Exception $e) {
				Mage::register('isSecureArea', true);
				Mage::app('admin');
				$order->delete();
				CPErrorHandler::logError("Exception during insert order \n" . $e->getMessage() . "\n" . $e->getTraceAsString());
				$apiOrder->orderHeader->status->hasError = true;
				$apiOrder->orderHeader->status->errorMessage = "Exception during insert order: " . $e->getMessage();
				$apiOrder->orderHeader->status->errorCode = CPResultCodes::SYSTEM_ERROR;
				return $apiOrder;
			}

			$items = $order->getAllItems();
			$orderItemsResponse = array();
			try {
				foreach ($items as $item) {
					foreach ($apiOrder->itemsOrdered as $orderItem) {
						if ($orderItem->article->id == $item->getSku() || $orderItem->article->id == $item->getProductId()) {
                            $item->setPrice($orderItem->costsSingle->net);
                            $item->setCustomPrice($orderItem->costsSingle->net);
                            $item->setBasePrice($orderItem->costsSingle->net);
                            $item->setOriginalCustomPrice($orderItem->costsSingle->net);
                            $item->setOriginalPrice($orderItem->costsSingle->net);
                            $item->setTaxAmount($orderItem->costsTotal->tax);
                            $item->setTaxPercent($orderItem->costsTotal->taxRate);
                            $item->setRowTotal($orderItem->costsTotal->net);
                            $item->setRowTotalInclTax($orderItem->costsTotal->gross);
                            $item->setPriceInclTax($orderItem->costsSingle->gross);
                            $item->setBaseOriginalPrice($orderItem->costsSingle->net);
                            $item->setBaseRowTotal($orderItem->costsTotal->net);
                            $item->setBasePriceInclTax($orderItem->costsSingle->gross);
                            $item->setBaseRowTotalInclTax($orderItem->costsTotal->gross);
							$item->save();
							$orderItem->id = $item->getId();
							$orderItemsResponse[] = $orderItem;
							try {
                                Mage::getModel('channelpilot/order_item')
                                    ->unsetData()
                                    ->setData(array(
                                        'order_item_id'             => $item->getId(),
                                        'marketplace_order_item_id' => $orderItem->idExternal,
                                        'order_id'                  => $order->getId(),
                                        'amount'                    => $orderItem->quantityOrdered,
                                    ))
                                    ->save();
							} catch (Exception $e) {
								self::deleteCPOrder($order->getId());
								Mage::register('isSecureArea', true);
								Mage::app('admin');
								$order->delete();
								CPErrorHandler::logError("Exception during insert order item: " . $e->getMessage() . "\n" . $e->getTraceAsString());
								$apiOrder->orderHeader->status->hasError = true;
								$apiOrder->orderHeader->status->errorMessage = "Exception during insert into: " . $e->getMessage();
								$apiOrder->orderHeader->status->errorCode = CPResultCodes::SYSTEM_ERROR;
								return $apiOrder;
							}
						}
					}
				}
			} catch (Exception $e) {
                $collection = Mage::getModel('channelpilot/order_item')->getCollection()
                    ->addFieldToFilter('order_id', array('eq' => $order->getId()));
                $collection->walk('delete');
                $marketplaceOrder = Mage::getModel('channelpilot/order')->load($order->getId());
                $$marketplaceOrder->delete();
				Mage::register('isSecureArea', true);
				Mage::app('admin');
				$order->delete();
				CPErrorHandler::logError("Exception during insert order" . $e->getMessage() . "\n" . $e->getTraceAsString());
				$apiOrder->orderHeader->status->hasError = true;
				$apiOrder->orderHeader->status->errorMessage = "Exception during insert order item: " . $e->getMessage();
				$apiOrder->orderHeader->status->errorCode = CPResultCodes::SYSTEM_ERROR;
				return $apiOrder;
			}
			$apiOrder->itemsOrdered = $orderItemsResponse;
			$order->setBaseSubtotal($apiOrder->summary->totalSumItems->net);
			$order->setBaseTaxAmount($apiOrder->summary->totalSumItems->tax);
//			$order->setBaseDiscountAmount(...);
			$order->setBaseShippingAmount($apiOrder->shipping->costs->gross);
			$order->setBaseGrandTotal($apiOrder->summary->totalSumOrder->gross);

			$order->setSubtotal($apiOrder->summary->totalSumItems->net);
			$order->setTaxAmount($apiOrder->summary->totalSumItems->tax);
//			$order->setDiscountAmount(...);
			$order->setShippingAmount($apiOrder->shipping->costs->gross);
			$order->setGrandTotal($apiOrder->summary->totalSumOrder->gross);

			$order->setCreatedAt($apiOrder->orderHeader->orderTime);

			$order->setBaseCurrencyCode($apiOrder->summary->currencyIso3);
			$order->setQuoteCurrencyCode($apiOrder->summary->currencyIso3);

			if(!empty($apiOrder->payment->paymentTime)) {
				$order->setData('state', Mage::getStoreConfig('channelpilot_marketplace/channelpilot_marketplace/channelpilot_orderStatusImportedPayed'));
				$order->setStatus(Mage::getStoreConfig('channelpilot_marketplace/channelpilot_marketplace/channelpilot_orderStatusImportedPayed'));
			} else {
				$order->setData('state', Mage::getStoreConfig('channelpilot_marketplace/channelpilot_marketplace/channelpilot_orderStatusImportedUnpayed'));
				$order->setStatus(Mage::getStoreConfig('channelpilot_marketplace/channelpilot_marketplace/channelpilot_orderStatusImportedUnpayed'));
			}

			$order->save();
		} catch (Exception $e) {
			CPErrorHandler::logError("Exception during importOrder: " . $e->getMessage() . "\n" . $e->getTraceAsString());
			$apiOrder->orderHeader->status->hasError = true;
			$apiOrder->orderHeader->status->errorMessage = "Exception during importOrder: " . $e->getMessage();
			$apiOrder->orderHeader->status->errorCode = CPResultCodes::SYSTEM_ERROR;
		}
		//$this->ordersImported[] = $order->getIncrementId();
		return $apiOrder;
	}

	/**
	 *
	 * @param type $id
	 */
	private function getProduct($id) {
		$selectedArticleId = Mage::getStoreConfig('channelpilot_general/channelpilot_general/channelpilot_articlenumber');
		$product = null;
		switch ($selectedArticleId) {
			case "product_id":
				$product = Mage::getModel('catalog/product')->load($id);
				if (!is_object($product)) {
					$product = null;
				}
				break;
			case "sku":
                $productId = Mage::getModel('catalog/product')->getIdBySku($id);
                if($productId) {
                    $product = Mage::getModel('catalog/product')->load($productId);
                    if (!is_object($product)) {
                        $product = null;
                    }
                }
				break;
			default:
				break;
		}
		return $product;
	}

	private function getCustomer($apiOrder) {
		$customer = Mage::getModel('customer/customer')
				->setWebsiteId($this->websiteId)
				->loadByEmail(CustomerFunctions::getUserName($apiOrder->customer->email));
		if ($customer->getId() == null) {
			$customer = Mage::getModel("customer/customer");
			$customer->website_id = $this->websiteId;
			$incrementId = Mage::getSingleton('eav/config')
					->getEntityType('customer')
					->fetchNewIncrementId($customer->getStoreId());
			$customer->setIncrementId($incrementId);
			$customer->setStore($this->store);
			$customer->firstname = $apiOrder->customer->nameFirst;
			$customer->lastname = $apiOrder->customer->nameLast;
			$customer->email = $apiOrder->customer->email;
			foreach ($apiOrder->customer->customerGroups as $userGroup) {
				$customer->setData('group_id', $userGroup->id);
			}
			if ($apiOrder->addressInvoice->genderId == 1) {
				$customer->setGender(
						Mage::getResourceModel('customer/customer')
								->getAttribute('gender')
								->getSource()
								->getOptionId('Male')
				);
			} else {
				if ($apiOrder->addressInvoice->genderId == 2) {
					$customer->setGender(
							Mage::getResourceModel('customer/customer')
									->getAttribute('gender')
									->getSource()
									->getOptionId('Female')
					);
				}
			}
			$customer->setCreatedAt($apiOrder->orderHeader->orderTime);
			$customer = CustomerFunctions::insertOrUpdateUser($customer, $apiOrder);
			$customer->save();

			$shippingAddress = Mage::getModel('customer/address');
			$shippingAddress->setCustomerId($customer->getId());
			$shippingAddress->setFirstname($apiOrder->addressDelivery->nameFirst);
			$shippingAddress->setLastname($apiOrder->addressDelivery->nameLast);
			$shippingAddress->setCountryId($apiOrder->addressDelivery->countryIso2);
			$shippingAddress->setStreet($apiOrder->addressDelivery->streetTitle . ' ' . $apiOrder->addressDelivery->streetNumber);
			$shippingAddress->setPostcode($apiOrder->addressDelivery->zip);
			$shippingAddress->setCity($apiOrder->addressDelivery->city);
			$shippingRegion = Mage::getModel('directory/region')->loadByName($apiOrder->addressDelivery->state, $apiOrder->addressDelivery->countryIso2);
			$shippingAddress->setRegion($shippingRegion->getName());
			$shippingAddress->setRegionId($shippingRegion->getId());
			if (substr(Mage::getVersion(), 2, 3) < 9) {
				$shippingAddress->setCompany($apiOrder->addressDelivery->company);
			}
			if (isset($apiOrder->addressDelivery->phone)) {
				$shippingAddress->setTelephone($apiOrder->addressDelivery->phone);
			}
			$shippingAddress = CustomerFunctions::createAddress($shippingAddress, $apiOrder);
			$shippingAddress->setIsDefaultShipping(true);
			$shippingAddress->save();
			$customer->setDefaultShipping($shippingAddress->getId());
			$customer->addAddress($shippingAddress);

			$billingAddress = Mage::getModel('customer/address');
			$billingAddress->setCustomerId($customer->getId());
			$billingAddress->setFirstname($apiOrder->addressInvoice->nameFirst);
			$billingAddress->setLastname($apiOrder->addressInvoice->nameLast);
			$billingAddress->setCountryId($apiOrder->addressInvoice->countryIso2);
			$billingAddress->setStreet($apiOrder->addressInvoice->streetTitle . ' ' . $apiOrder->addressInvoice->streetNumber);
			$billingAddress->setPostcode($apiOrder->addressInvoice->zip);
			$billingAddress->setCity($apiOrder->addressInvoice->city);
			$billingRegion = Mage::getModel('directory/region')->loadByName($apiOrder->addressInvoice->state, $apiOrder->addressInvoice->countryIso2);
			$billingAddress->setRegion($billingRegion->getName());
			$billingAddress->setRegionId($billingRegion->getId());
			if (substr(Mage::getVersion(), 2, 3) < 9) {
				$billingAddress->setCompany($apiOrder->addressInvoice->company);
			}
			if (isset($apiOrder->addressInvoice->phone)) {
				$billingAddress->setTelephone($apiOrder->addressInvoice->phone);
			}
			$billingAddress = CustomerFunctions::createAddress($billingAddress, $apiOrder);
			$billingAddress->setIsDefaultBilling(true);
			$billingAddress->save();

			$customer->setDefaultBilling($billingAddress->getId());
			$customer->addAddress($billingAddress);
		} else {
			$customer->firstname = $apiOrder->customer->nameFirst;
			$customer->lastname = $apiOrder->customer->nameLast;
			foreach ($apiOrder->customer->customerGroups as $userGroup) {
				$customer->setData('group_id', $userGroup->id);
			}
			if ($apiOrder->addressInvoice->genderId == 1) {
				$customer->setGender(
						Mage::getResourceModel('customer/customer')
								->getAttribute('gender')
								->getSource()
								->getOptionId('Male')
				);
			} else {
				if ($apiOrder->addressInvoice->genderId == 2) {
					$customer->setGender(
							Mage::getResourceModel('customer/customer')
									->getAttribute('gender')
									->getSource()
									->getOptionId('Female')
					);
				}
			}
			$customer->save();

			$shippingAddress = Mage::getModel('customer/address')->load($customer->getDefaultShipping());
			$shippingAddress->setCustomerId($customer->getId());
			$shippingAddress->setFirstname($apiOrder->addressDelivery->nameFirst);
			$shippingAddress->setLastname($apiOrder->addressDelivery->nameLast);
			$shippingAddress->setCountryId($apiOrder->addressDelivery->countryIso2);
			$shippingAddress->setStreet($apiOrder->addressDelivery->streetTitle . ' ' . $apiOrder->addressDelivery->streetNumber);
			$shippingAddress->setPostcode($apiOrder->addressDelivery->zip);
			$shippingAddress->setCity($apiOrder->addressDelivery->city);
			$shippingRegion = Mage::getModel('directory/region')->loadByName($apiOrder->addressDelivery->state, $apiOrder->addressDelivery->countryIso2);
			$shippingAddress->setRegion($shippingRegion->getName());
			$shippingAddress->setRegionId($shippingRegion->getId());
			if (substr(Mage::getVersion(), 2, 3) < 9) {
				$shippingAddress->setCompany($apiOrder->addressDelivery->company);
			}
			if (isset($apiOrder->addressDelivery->phone)) {
				$shippingAddress->setTelephone($apiOrder->addressDelivery->phone);
			}
			$shippingAddress = CustomerFunctions::createAddress($shippingAddress, $apiOrder);
			$shippingAddress->setIsDefaultShipping(true);
			$shippingAddress->save();

			$billingAddress = Mage::getModel('customer/address')->load($customer->getDefaultBilling());
			$billingAddress->setCustomerId($customer->getId());
			$billingAddress->setFirstname($apiOrder->addressInvoice->nameFirst);
			$billingAddress->setLastname($apiOrder->addressInvoice->nameLast);
			$billingAddress->setCountryId($apiOrder->addressInvoice->countryIso2);
			$billingAddress->setStreet($apiOrder->addressInvoice->streetTitle . ' ' . $apiOrder->addressInvoice->streetNumber);
			$billingAddress->setPostcode($apiOrder->addressInvoice->zip);
			$billingAddress->setCity($apiOrder->addressInvoice->city);
			$billingRegion = Mage::getModel('directory/region')->loadByName($apiOrder->addressInvoice->state, $apiOrder->addressInvoice->countryIso2);
			$billingAddress->setRegion($billingRegion->getName());
			$billingAddress->setRegionId($billingRegion->getId());
			if (substr(Mage::getVersion(), 2, 3) < 9) {
				$billingAddress->setCompany($apiOrder->addressInvoice->company);
			}
			if (isset($apiOrder->addressInvoice->phone)) {
				$billingAddress->setTelephone($apiOrder->addressInvoice->phone);
			}
			$billingAddress = CustomerFunctions::createAddress($billingAddress, $apiOrder);
			$billingAddress->setIsDefaultBilling(true);
			$billingAddress->save();
		}
		return $customer;
	}

	/**
	 *
	 * @param type $apiOrder
	 * @return boolean
	 */
	private function getOrderItems($apiOrder) {
		$dbOrderItems = array();

        $itemCollection = Mage::getModel('channelpilot/order_item')->getCollection()
            ->addFieldToSelect(array('order_item_id', 'marketplace_order_item_id'))
            ->addMarketplaceOrderFilter($apiOrder->orderHeader->orderIdExternal, $apiOrder->orderHeader->source);

		foreach ($itemCollection->getData() AS $resultType) {
			$dbOrderItems[$resultType['marketplace_order_item_id']] = $resultType['order_item_id'];
		}
		$orderItemsResponse = array();

		foreach ($apiOrder->itemsOrdered as $orderItem) {
			if (empty($dbOrderItems[$orderItem->idExternal])) {
				$apiOrder->orderHeader->status->hasError = true;
				$apiOrder->orderHeader->status->errorMessage = "Can't find order article " . $orderItem->article->id . " from EXISTING order: " . $apiOrder->orderHeader->orderId;
				$apiOrder->orderHeader->status->errorCode = CPResultCodes::SHOP_ERROR_ARTICLE_UNKNOWN_EXISTING_ORDER;
				return $apiOrder;
			} else {
				$orderItem->id = $dbOrderItems[$orderItem->idExternal];
				$orderItemsResponse[] = $orderItem;
			}
		}
		$apiOrder->itemsOrdered = $orderItemsResponse;
		return $apiOrder;
	}

	private function getOrderId($externalOrderId, $source) {
        $order = Mage::getModel('channelpilot/order')->loadByMarketplaceOrderIdAndMarketplace($externalOrderId, $source);
        return ($order && $order->getId()) ? $order->getId() : null;
	}

	private function deleteCPOrder($orderId) {
        $collection = Mage::getModel('channelpilot/order_item')->getCollection()
            ->addFieldToFilter('order_id', array('eq' => $orderId));
        $collection->walk('delete');

        $collection = Mage::getModel('channelpilot/order')->getCollection()
            ->addFieldToFilter('order_id', array('eq' => $orderId));
        $collection->walk('delete');
	}

	private function getOrdersFromDb() {
		$orders = array();

        $collection = Mage::getModel('channelpilot/order_item')->getCollection()
            ->addFieldToSelect(array(
                'orderItemId' => 'order_item_id',
                'externalOrderItemId' => 'marketplace_order_item_id'
            ))
            ->addReadyForExportFilter($this->shopId);

		try {
			$sResult = $collection->getData();
			if (!empty($sResult)) {
				$order = null;
				$orderId = null;
				foreach ($sResult AS $resultType) {
					if (empty($orderId) || $orderId != $resultType['orderId']) {
						if (!empty($orderId)) {
							$orders[] = $order;
						}
						$order = new CPOrder();
						$order->orderHeader = new CPOrderHeader($resultType['externalOrderId'], $resultType['orderId'], $resultType['source'], $resultType['status'], null, false);
						$orderId = $resultType['orderId'];
					}
					$item = new CPOrderItem();
					$item->id = $resultType['orderItemId'];
					$item->idExternal = $resultType['externalOrderItemId'];
					$order->itemsOrdered[] = $item;
				}
				$orders[] = $order;
			}
			return $orders;
		} catch (Exception $e) {
			CPErrorHandler::handle(CPResultCodes::SYSTEM_ERROR, "Exception in getOrdersFromDb(): " . $e->getMessage(), "Exception in getOrdersFromDb():" . $e->getMessage());
		}
	}
}
