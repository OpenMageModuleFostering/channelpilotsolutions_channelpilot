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
 * @subpackage		block_adminhtml_field
 * @copyright       Copyright (c) 2012 <info@channelpilot.com> - www.channelpilot.com
 * @author          Björn Wehner <info@channelpilot.com>
 * @license         <http://www.gnu.org/licenses/> GNU General Public License (GPL 3)
 * @link            http://www.channelpilot.com
 */

abstract class Channelpilotsolutions_Channelpilot_Block_Adminhtml_Field_Abstract extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract {

    /**
     * Get all product attribute codes as array. Returned format:
     * [0] =>   Array
     *          (
     *              [attribute_code] => code
     *          )
     * @param   array     $excludeAttributeCodes     Attribute codes used for a NOT IN filter
     * @return  array
     */
    protected function _getProductAttributeCodes(array $excludeAttributeCodes = array()) {
        $query = new Zend_Db_Select(Mage::getSingleton('core/resource')->getConnection('core_read'));
        $query->from(Mage::getSingleton('core/resource')->getTableName('eav_entity_type'), array('entity_type_id'))
            ->where('entity_type_code = ?', 'catalog_product');
        return Mage::getResourceModel('eav/entity_attribute_collection')
            ->addFieldToSelect('attribute_code')
            ->addFieldToFilter('entity_type_id', array('eq' => new Zend_Db_Expr('('.$query.')')))
            ->addFieldToFilter('attribute_code', array('nin' => $excludeAttributeCodes))
            ->getData();
    }
}