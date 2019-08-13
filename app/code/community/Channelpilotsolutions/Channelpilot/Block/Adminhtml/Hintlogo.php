<?php

class Channelpilotsolutions_Channelpilot_Block_Adminhtml_Hintlogo extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface {

	protected $_template = 'channelpilotsolutions/config_hint.phtml';

	public function render(Varien_Data_Form_Element_Abstract $element) {
		return $this->toHtml();
	}

}
