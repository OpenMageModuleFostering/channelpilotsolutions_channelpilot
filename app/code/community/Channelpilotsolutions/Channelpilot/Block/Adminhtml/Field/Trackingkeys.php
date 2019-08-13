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
class Channelpilotsolutions_Channelpilot_Block_Adminhtml_Field_Trackingkeys extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract {

	protected $magentoAttributes;

	public function __construct() {
		$this->addColumn('shop', array(
			'label' => Mage::helper('adminhtml')->__('Shop'),
			'size' => 15
		));
		$this->addColumn('trackingkey', array(
			'label' => Mage::helper('adminhtml')->__('TrackingKey'),
			'size' => 28
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

		if ($columnName == 'shop') {
			$rendered = '<select name="' . $inputName . '">';
            $storeCollection = Mage::getSingleton('core/store')->getCollection()
                ->addFieldToFilter('code', array('neq' => Mage_Core_Model_Store::ADMIN_CODE))
                ->setOrder('website_id', 'ASC');

            foreach ($storeCollection as $store) {
                $rendered .= '<option value="' . $store->getId() . '">' . $store->getName() . '</option>';
            }

			$rendered .= '</select>';
			return $rendered;
		}
		return '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}"/>';
	}

}

?>