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
class Channelpilotsolutions_Channelpilot_Model_Adminhtml_Source_Producturlgeneration
{
    const   METHOD_URL_PATH         = 1;
    const   METHOD_URL_KEY          = 2;
    const   METHOD_URL_KEY_HTML     = 3;
    const   METHOD_GET_PRODUCT_URL  = 4;
    const   METHOD_GET_URL_IN_STORE = 5;

    public function toOptionArray() {
        return array(
            array('value' => self::METHOD_URL_PATH, 'label' => Mage::helper('channelpilot')->__("CP base url + url_path")),
            array('value' => self::METHOD_URL_KEY, 'label' => Mage::helper('channelpilot')->__("CP base url + url_key")),
            array('value' => self::METHOD_URL_KEY_HTML, 'label' => Mage::helper('channelpilot')->__("CP base url + url_key + .html")),
            array('value' => self::METHOD_GET_PRODUCT_URL, 'label' => Mage::helper('channelpilot')->__("CP Method getProductUrl")),
            array('value' => self::METHOD_GET_URL_IN_STORE, 'label' => Mage::helper('channelpilot')->__("CP Method getUrlInStore")),
        );
    }
}