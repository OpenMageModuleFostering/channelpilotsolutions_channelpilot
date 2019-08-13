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
 * @author          Bj�rn Wehner <info@channelpilot.com>
 * @license         <http://www.gnu.org/licenses/> GNU General Public License (GPL 3)
 * @link            http://www.channelpilot.com
 */

// getModuleDir does not return Helper directory for some Magento Versions ...
require_once Mage::getModuleDir('','Channelpilotsolutions_Channelpilot').DS.'Helper'.DS.'handler'.DS.'CPAbstractHandler.php';

class Channelpilotsolutions_Channelpilot_Model_Carrier_Cpshipping
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    protected $_code = 'cpshipping';
    protected $_isFixed = true;

    /**
     * Checks if user is logged in as admin
     *
     * @return bool
     */
    protected function isAdmin() {
        $token = Mage::app()->getRequest()->getParam('token', false);
        if($token) {
            if(CPAbstractHandler::isIpAllowedViaSecurityToken($token, false)) {
                return true;
            }
        }

        $currentSessionName = Mage::getSingleton('core/session')->getSessionName();

        /* set admin session */
        Mage::getSingleton('core/session', array('name' => 'adminhtml'))->start();
        $isLoggedIn = Mage::getSingleton('admin/session', array('name' => Mage_Adminhtml_Controller_Action::SESSION_NAMESPACE))->isLoggedIn();
        /* set original session */
        Mage::getSingleton('core/session', array('name' => $currentSessionName))->start();
        return $isLoggedIn;
    }

    /**
     * Returns the shipping rate for 'cpshipping'.
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request) {
        if(!$this->getConfigFlag('active')) {
            return false;
        }

        if ($this->getConfigFlag('backend_only') && !$this->isAdmin()) {
            return false;
        }

        $method = Mage::getModel('shipping/rate_result_method')
            ->setCarrier('cpshipping')
            ->setCarrierTitle($this->getConfigData('title'))
            ->setMethod('cpshipping')
            ->setMethodTitle($this->getConfigData('name'))
            ->setPrice($this->getConfigData('price'))
            ->setCost($this->getConfigData('price'));

        return Mage::getModel('shipping/rate_result')
            ->append($method);
    }

    public function getAllowedMethods() {
        return array('cpshipping'=>$this->getConfigData('name'));
    }
}