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
 * @subpackage		adminhtml_model_cookiemode
 * @copyright       Copyright (c) 2012 <info@channelpilot.com> - www.channelpilot.com
 * @author          Peter Hoffmann <info@channelpilot.com>
 * @license         <http://www.gnu.org/licenses/> GNU General Public License (GPL 3)
 * @link            http://www.channelpilot.com
 */
class Channelpilotsolutions_Channelpilot_Adminhtml_Model_Orderstatus_Values {

	public function toOptionArray() {
		
		$sQuery = "select * from sales_order_status;";
		$dbConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
			try {
				$sResult = $dbConnection->fetchAll($sQuery);
				$result_array = array();
				$result_array[] = array('value' => "---", 'label' => "---");
				foreach ($sResult as $resultType) {
					$result_array[] = array('value' => $resultType['status'], 'label' => $resultType['label']);
				}
			} catch (Exception $e) {
				$dbConnection->closeConnection();
			}
		$dbConnection->closeConnection();

		return $result_array;
	}

}

?>