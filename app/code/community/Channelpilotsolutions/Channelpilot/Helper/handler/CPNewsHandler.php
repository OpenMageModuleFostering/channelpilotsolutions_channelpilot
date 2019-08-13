<?php

/**
 * an cp status handler
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPNewsHandler extends CPAbstractHandler {

	/**
	 * Handle status event
	 */
	public function handle() {
        $priority = Mage::app()->getRequest()->getParam('priority', false);
        $date = Mage::app()->getRequest()->getParam('date', false);
        $title = Mage::app()->getRequest()->getParam('title', false);
        $description = Mage::app()->getRequest()->getParam('description', false);
        $url = Mage::app()->getRequest()->getParam('url', false);
        if ($priority && $date && $title && $description && $url) {
			$message = Mage::getModel('adminnotification/inbox')->parse(array(
				array(
					'severity' => (int) $priority,
					'date_added' => $date,
					'title' => $title,
					'description' => $description,
					'url' => $url,
					'internal' => true
				)
			));
			$hook = new CPHookResponse();
			$hook->resultCode = CPResultCodes::SUCCESS;
			$hook->resultMessage = "News received";
			$hook->writeResponse(self::defaultHeader, json_encode($hook));
		} else {
			$hook = new CPHookResponse();
			$hook->resultCode = CPResultCodes::SYSTEM_ERROR;
			$hook->resultMessage = "Not enough parameter set";
			$hook->writeResponse(self::defaultHeader, json_encode($hook));
		}
	}

}

?>
