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
 * @subpackage		helper
 * @copyright       Copyright (c) 2013 <info@channelpilot.com> - www.channelpilot.com
 * @author          Peter Hoffmann <info@channelpilot.com>
 * @license         <http://www.gnu.org/licenses/> GNU General Public License (GPL 3)
 * @link            http://www.channelpilot.com
 */
require_once 'CPErrors.php';

//	Handler
require_once 'handler/CPAbstractHandler.php';
require_once 'handler/CPErrorHandler.php';
require_once 'handler/CPExportHandler.php';
require_once 'handler/CPStatusHandler.php';
require_once 'handler/CPRegisterHandler.php';
require_once 'handler/CPNewPriceHandler.php';
require_once 'handler/CPOrderHandler.php';
require_once 'handler/CPDeliveryHandler.php';
require_once 'handler/CPCancellationHandler.php';
require_once 'handler/CPNewsHandler.php';
require_once 'handler/CPDebugHandler.php';
require_once 'handler/CPPaymentHandler.php';

//	RESPONSES
require_once 'responses/CPHookResponse.php';
require_once 'responses/CPGetStatusHookResponse.php';
require_once 'responses/CPRegisterHookResponse.php';

//	API
require_once 'api/3_2/thin/CPDelivery.php';
require_once 'api/3_2/ChannelPilotSellerAPI_v3_2.php';
require_once 'api/3_2/CPResultCodes.php';

//	special customer functions
require_once 'special/CustomerFunctions.php';

class Channelpilotsolutions_Channelpilot_Helper_Data extends Mage_Core_Helper_Abstract {

	const GET_REGISTER = "register";
	const GET_EXPORT = "export";
	const GET_STATUS = "status";
	const GET_IMPORT_ORDERS = "orders";
	const GET_DELIVERED = "deliveries";
	const GET_CANCELLATION = "cancellations";
	const GET_NEWS = "news";
	const GET_NEWPRICES = "prices";
	const GET_DEBUG = "debug";
    const GET_PAYMENTS = "payments";

	public function __construct($root = 'root') {

	}

	public function createXml() {
		if (Mage::getStoreConfig('channelpilot_export/channelpilot_productfeed/channelpilot_useExport')) {
			$password = Mage::getStoreConfig('channelpilot_export/channelpilot_productfeed/channelpilot_password');
            $paramPassword = Mage::app()->getRequest()->getParam('password', false);
			if ($password == '' || ($paramPassword AND $paramPassword == $password)) {
                $handler = new CPExportHandler(CPExportHandler::METHOD_XML);
                $handler->handle();
				exit();
			}
		}
		return;
	}

	//	API CONNECTOR
	public function api() {
		$hook = null;
		if ($this->checkIp() === false) {
			return;
		}

		$newsActive = false;
		if (Mage::getStoreConfig('channelpilot_general/channelpilot_general/channelpilot_useNews')) {
			$newsActive = true;
		}
		$marketplaceActive = false;
		if (Mage::getStoreConfig('channelpilot_marketplace/channelpilot_marketplace/channelpilot_useMarketplaces')) {
			$marketplaceActive = true;
		}
		$pricecontrolActive = false;
		if (Mage::getStoreConfig('channelpilot_pricecontrol/channelpilot_general/channelpilot_usePricecontrol')) {
			$pricecontrolActive = true;
		}
		$exportActive = false;
		if (Mage::getStoreConfig('channelpilot_export/channelpilot_productfeed/channelpilot_useExport')) {
			$exportActive = true;
		}

        $method = Mage::app()->getRequest()->getParam('method', '');
		switch ($method) {
			// Send method
			case self::GET_STATUS:
				$this->checkActivation(array($marketplaceActive, $pricecontrolActive), 'marketplace OR pricecontrol');
				$handler = new CPStatusHandler();
				$hook = $handler->handle();
				break;

			// Send method + merchantid + multishopid + token + ips
			case self::GET_REGISTER:
				$this->checkActivation(array($marketplaceActive, $pricecontrolActive), 'marketplace OR pricecontrol');
				$this->checkSignature();
				$handler = new CPRegisterHandler();
				$hook = $handler->handle();
				break;

			//	Send Method + token
			case self::GET_IMPORT_ORDERS:
				$this->checkActivation(array($marketplaceActive), 'marketplace');
				$this->checkSignature();
				$handler = new CPOrderHandler();
				$hook = $handler->handle();
				break;
			// Send Method + token
			case self::GET_DELIVERED:
				$this->checkActivation(array($marketplaceActive), 'marketplace');
				$this->checkSignature();
				$handler = new CPDeliveryHandler();
				$hook = $handler->handle();
				break;

			// Send Method + token
			case self::GET_CANCELLATION:
				$this->checkActivation(array($marketplaceActive), 'marketplace');
				$this->checkSignature();
				$handler = new CPCancellationHandler();
				$hook = $handler->handle();
				break;

			// Send method
			case self::GET_NEWS:
				$this->checkActivation(array($newsActive), 'news');
				$this->checkSignature();
				$handler = new CPNewsHandler();
				$hook = $handler->handle();
				break;

			// Send method + token + priceId
			case self::GET_NEWPRICES:
				$this->checkActivation(array($pricecontrolActive), 'pricecontrol');
				$handler = new CPNewPriceHandler();
				$hook = $handler->handle();
				break;

			// Send method + limit + shopId ( + last)
			case self::GET_DEBUG:
				$this->checkSignature();
				$handler = new CPDebugHandler();
				$hook = $handler->handle();
				break;

            case self::GET_PAYMENTS:
                $this->checkSignature();
                $this->checkActivation(array($marketplaceActive), 'marketplace');
                $handler = new CPPaymentHandler();
                $handler->handle();
                break;

			default:
				$hook = "not supported method: " . $method;
				break;
		}
		header("Content-Type: application/json;");
		print_r(json_encode($hook));
		exit();
	}

    protected function checkSignature() {
        $php = Mage::app()->getRequest()->getParam('php', false);
        $shop = Mage::app()->getRequest()->getParam('shop', false);
        $plugin = Mage::app()->getRequest()->getParam('plugin', false);
		IF ($php && $shop && $plugin) {
			if ($php == phpversion() && $shop == CPHookResponse::getSignatureShop() && $plugin == CPHookResponse::getModuleVersion()) {
				return true;
			}
			CPErrorHandler::handle(CPErrors::RESULT_SIGNATURE_MISMATCH, "Signature changed", "Signature changed \n" . $php . " -> " . phpversion() . "\n" . $shop . " -> " . CPHookResponse::getSignatureShop() . "\n" . $plugin . " -> " . CPHookResponse::getModuleVersion());
		} else {
			CPErrorHandler::handle(CPErrors::RESULT_MISSING_PARAMS, "Missing params for signature check", "Missing params for signature check");
		}
	}

    protected function checkActivation($configs, $function) {
		foreach ($configs as $config) {
			if ($config === true) {
				return true;
			}
		}
		CPErrorHandler::handle(CPErrors::RESULT_API_DEACTIVATED, "'$function' not activated", "'$function' not activated");
	}

    protected function checkIp() {
		if (Mage::getStoreConfig('channelpilot_general/channelpilot_general/channelpilot_checkIp')) {
            return Mage::getModel('channelpilot/registration')->isIpAuthorized(Mage::app()->getRequest()->getClientIp());
		}
		return true;
	}

    public function getAllStoreIds() {
        $storeIds = array();

        /** @var  $website Mage_Core_Model_Website */
        foreach(Mage::app()->getWebsites() as $website) {
            $storeIds = array_merge($storeIds, $website->getStoreIds());
        }

        return $storeIds;
    }
}

?>