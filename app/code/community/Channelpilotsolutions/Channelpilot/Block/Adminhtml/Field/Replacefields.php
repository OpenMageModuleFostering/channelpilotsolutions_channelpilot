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
class Channelpilotsolutions_Channelpilot_Block_Adminhtml_Field_Replacefields extends Channelpilotsolutions_Channelpilot_Block_Adminhtml_Field_Abstract {

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
            $attributes = $this->_getProductAttributeCodes(array(
                'sku','price','name','description','manufacturer','color','weight'
            ));

			$attributes[]['attribute_code'] = 'qty';
			$attributes[]['attribute_code'] = 'stock_status';
			$attributes[]['attribute_code'] = 'cp_color_attribute_id';
			$attributes[]['attribute_code'] = 'parent_id';

			$attributes[]['attribute_code'] = 'categories';
			$attributes[]['attribute_code'] = 'cp_image_url';
			$attributes[]['attribute_code'] = 'cp_product_url';
			$attributes[]['attribute_code'] = 'cp_additional_image_1';
			$attributes[]['attribute_code'] = 'cp_additional_image_2';
			$attributes[]['attribute_code'] = 'cp_additional_image_3';
			asort($attributes);
			foreach ($attributes as $attribute) {
				$rendered .= '<option value="' . $attribute['attribute_code'] . '">' . $attribute['attribute_code'] . '</option>';
			}
			$rendered .= '</select>';
			return $rendered;
		}

		return '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}"/>';
	}

}

?>