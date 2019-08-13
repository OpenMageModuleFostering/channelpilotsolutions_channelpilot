<?php

/**
 * an cp export handler
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPDebugHandler extends CPAbstractHandler {

	/**
	 * Handle status event
	 *
	 */
	public function handle() {
        $token = Mage::app()->getRequest()->getParam('token', false);
        if ($token && self::isIpAllowedViaSecurityToken($token)) {
            $limit = Mage::app()->getRequest()->getParam('limit', 0);
            if ($limit) {
                $logEntries = Mage::getModel('channelpilot/logs')->getCollection()
                    ->setPageSize($limit)
                    ->setOrder('id', 'DESC');

                $hook = new CPHookResponse();
                $hook->resultCode = CPResultCodes::SUCCESS;
                $hook->logs = $logEntries->getData();
                $hook->resultMessage = "LoggingData of " . sizeof($hook->logs) . " entries";
                $hook->moreAvailable = true;
                if (sizeof($hook->logs) < $limit) {
                    $hook->moreAvailable = false;
                }
                $hook->writeResponse(self::defaultHeader, json_encode($hook));
            } else {
                $hook = new CPHookResponse();
                $hook->resultCode = CPResultCodes::SYSTEM_ERROR;
                $hook->resultMessage = ($limit == 0) ? "No data to display" : "Not enough parameter set";
                $hook->writeResponse(self::defaultHeader, json_encode($hook));
            }
        } else {
            if (empty($token)) {
                CPErrorHandler::handle(CPErrors::RESULT_MISSING_PARAMS, "no token found", "no token found");
            } else {
                CPErrorHandler::handle(CPErrors::RESULT_FAILED, "ip not allowed by token: " . $token, "ip not allowed by token: " . $token);
            }
        }
	}

}

?>
