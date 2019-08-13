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
class Channelpilotsolutions_Channelpilot_Block_Adminhtml_Field_Specialfields extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract {

	protected $magentoAttributes;

	public function __construct() {
		$this->addColumn('name', array(
			'label' => Mage::helper('adminhtml')->__('Data field name'),
			'size' => 30
		));
		$this->addColumn('value', array(
			'label' => Mage::helper('adminhtml')->__('Data field value'),
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


		return '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}"/>';
	}

}

?>