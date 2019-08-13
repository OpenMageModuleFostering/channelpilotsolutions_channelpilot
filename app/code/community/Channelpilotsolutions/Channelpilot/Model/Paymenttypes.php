<?php

class Channelpilotsolutions_Channelpilot_Model_Paymenttypes {

	public function toOptionArray() {
		$options = array();

		foreach (Mage::getSingleton('channelpilot/payment')->getCcTypes() as $code => $name) {
			$options[] = array(
				'value' => $code,
				'label' => $name
			);
		}

		return $options;
	}

}
