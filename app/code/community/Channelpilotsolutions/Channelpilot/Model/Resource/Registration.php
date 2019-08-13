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

class Channelpilotsolutions_Channelpilot_Model_Resource_Registration extends Mage_Core_Model_Resource_Db_Abstract {

    const SECURITY_TOKEN    = 'securityToken';
    const SHOP_ID           = 'shopId';

    /**
     * Model Initialization
     *
     */
    protected function _construct() {
        $this->_init('channelpilot/registration', 'id');
        $this->_isPkAutoIncrement = false;
    }

    /**
     * Get the exploded value from ips_authorized for a value via a given field.
     * @param   string    $value
     * @param   string    $field
     * @return  array
     */
    protected function _getAllowedIpsViaField($value, $field = self::SECURITY_TOKEN) {
        if($field == self::SECURITY_TOKEN || $field == self::SHOP_ID) {
            $read = $this->getReadConnection();
            if($read) {
                $query = $read->select()
                    ->from($this->getMainTable(), array('ips_authorized'))
                    ->where($read->quoteIdentifier($field). ' = ?', $value);

                $result = $read->fetchAll($query);

                if(!empty($result)) {
                    $ipsAuthorized = array();
                    foreach($result as $row) {
                        $ipsAuthorized = array_merge($ipsAuthorized, explode(";", $row['ips_authorized']));
                    }

                    return $ipsAuthorized;
                }
            }
        }
        return array();
    }

    /**
     * Get all ips_authorized via a given security token.
     * @param   string    $securityToken
     * @return  array
     */
    public function getAllowedIpsViaSecurityToken($securityToken) {
        return $this->_getAllowedIpsViaField($securityToken);
    }

    /**
     * Get all ips_authorized via a shopId.
     * @param   string    $securityToken
     * @return  array
     */
    public function getAllowedIpsViaShopId($shopId) {
        return $this->_getAllowedIpsViaField($shopId, self::SHOP_ID);
    }

    /**
     * Checks if the given $ip is authorized.
     * @param       string      $ip
     * @return      bool
     */
    public function isIpAuthorized($ip) {
        $read = $this->getReadConnection();
        if($read) {
            $select = $read->select()
                ->from($this->getMainTable())
                ->where('ips_authorized LIKE ' .$read->quote('%'.$ip.'%'));

            $result = $read->fetchAll($select);
            if(!empty($result)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Load by the fields shopId and securityToken
     * @param $shopId
     * @param $token
     * @param Channelpilotsolutions_Channelpilot_Model_Registration $object
     * @return Channelpilotsolutions_Channelpilot_Model_Registration
     */
    public function loadByShopIdAndToken($shopId, $token, Channelpilotsolutions_Channelpilot_Model_Registration $object) {
        $read = $this->getReadConnection();
        if($read) {
            $select = $read->select()
                ->from($this->getMainTable())
                ->where('shopId = ?', $shopId)
                ->where('securityToken = ?', $token);

            $result = $read->fetchRow($select);
            if(!empty($result)) {
                $object->setData($result);
            }
        }

        return $object;
    }
}