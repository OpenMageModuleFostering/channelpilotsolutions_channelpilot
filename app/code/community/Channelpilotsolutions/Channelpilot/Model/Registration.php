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

/**
 * Class Channelpilotsolutions_Channelpilot_Model_Registration
 *
 * @method  string      getId()
 * @method  string      getShopId()
 * @method  string      getIpsAuthorized()
 * @method  string      getMerchantId()
 * @method  string      getSecurityToken()
 * @method  int         getLastStockUpdate()
 * @method  int         getLastPriceUpdate()
 * @method  int         getLastCatalogUpdate()
 */

class Channelpilotsolutions_Channelpilot_Model_Registration extends Mage_Core_Model_Abstract {

    /**
     * Initialize resource model
     */
    protected function _construct() {
        $this->_init('channelpilot/registration');
    }

    /**
     * Get all ips_authorized via a given security token.
     * @param   string    $securityToken
     * @return  array
     */
    public function getAllowedIpsViaSecurityToken($securityToken) {
        return $this->_getResource()->getAllowedIpsViaSecurityToken($securityToken);
    }

    /**
     * Get all ips_authorized via a shopId.
     * @param   string    $securityToken
     * @return  array
     */
    public function getAllowedIpsViaShopId($shopId) {
        return $this->_getResource()->getAllowedIpsViaShopId($shopId);
    }

    /**
     * Checks if the given $ip is authorized.
     * @param       string      $ip
     * @return      bool
     */
    public function isIpAuthorized($ip) {
        if(CPAbstractHandler::ChannelPilot_IP == $ip) {
            return true;
        }
        return $this->_getResource()->isIpAuthorized($ip);
    }

    /**
     * Load by the fields shopId and securityToken
     * @param $shopId
     * @param $token
     * @return Channelpilotsolutions_Channelpilot_Model_Registration
     */
    public function loadByShopIdAndToken($shopId, $token) {
        return $this->_getResource()->loadByShopIdAndToken($shopId, $token, $this);
    }
}