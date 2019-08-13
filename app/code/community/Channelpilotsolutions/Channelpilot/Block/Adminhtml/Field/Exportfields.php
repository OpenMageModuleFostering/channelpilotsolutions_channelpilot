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
 * @author          Peter Hoffmann <info@channelpilot.com>
 * @license         <http://www.gnu.org/licenses/> GNU General Public License (GPL 3)
 * @link            http://www.channelpilot.com
 */
class Channelpilotsolutions_Channelpilot_Block_Adminhtml_Field_Exportfields extends Channelpilotsolutions_Channelpilot_Block_Adminhtml_Field_Abstract {

	public function __construct() {
		$this->addColumn('productattribute', array(
			'label' => Mage::helper('adminhtml')->__('Data field'),
			'size' => 30
		));
		$this->_addAfter = false;

		parent::__construct();
		$this->setTemplate('channelpilotsolutions/array_dropdown.phtml');
	}

	protected function _renderCellTemplate($columnName) {
		if (empty($this->_columns[$columnName])) {
			throw new Exception('Wrong column name specified.');
		}
		$inputName = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

		if ($columnName == 'productattribute') {
			$rendered = '<select name="' . $inputName . '">';

			// Get attribute codes and types
            $attributes = $this->_getProductAttributeCodes(array(
                'sku','price','name','description','manufacturer','color','weight', 'parent_id'
            ));

			$attributes[]['attribute_code'] = 'qty';
			$attributes[]['attribute_code'] = 'stock_status';
			$attributes[]['attribute_code'] = 'cp_color_attribute_id';
            $attributes[]['attribute_code'] = 'type_id';
            $attributes[]['attribute_code'] = 'parent_id';
            $attributes[]['attribute_code'] = 'min_sale_qty';
            $attributes[]['attribute_code'] = 'max_sale_qty';
			asort($attributes);
			foreach ($attributes as $attribute) {
                if($attribute['attribute_code'] == 'group_price') {
                    $customerGroupCollection = Mage::getModel('customer/group')->getCollection();
                    $strGroupPrice = 'group_price';
                    foreach($customerGroupCollection as $group) {
                        $rendered .= '<option value="' . $strGroupPrice.'_'.$group->getId(). '">' . $strGroupPrice.' - '.$group->getCustomerGroupCode() . '</option>';
                    }
                } else {
                    $rendered .= '<option value="' . $attribute['attribute_code'] . '">' . $attribute['attribute_code'] . '</option>';
                }
			}
			$rendered .= '</select>';
			return $rendered;
		}

		return '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}"/>';
	}

}

?>