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
class Channelpilotsolutions_Channelpilot_Model_Adminhtml_Source_Exportmethod
{
    const   EXPORT_METHOD_LIVE      = 1;
    const   EXPORT_METHOD_INDEXED   = 2;

    public function toOptionArray() {
        return array(
            array('value' => self::EXPORT_METHOD_LIVE, 'label' => Mage::helper('channelpilot')->__('CP Live')),
            array('value' => self::EXPORT_METHOD_INDEXED, 'label' => Mage::helper('channelpilot')->__('CP Indexed')),
        );
    }
}