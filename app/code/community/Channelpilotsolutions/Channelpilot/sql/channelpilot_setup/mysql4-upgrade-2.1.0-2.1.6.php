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
 * @copyright       Copyright (c) 2012 <info@channelpilot.com> - www.channelpilot.com
 * @author          Björn Wehner <info@channelpilot.com>
 * @license         <http://www.gnu.org/licenses/> GNU General Public License (GPL 3)
 * @link            http://www.channelpilot.com
 */

$installer = $this;

$installer->startSetup();

$tableName = Mage::getSingleton('core/resource')->getTableName('channelpilot/order_item');
$dbname = (string)Mage::getConfig()->getNode('global/resources/default_setup/connection/dbname');

/** @var  $write Magento_Db_Adapter_Pdo_Mysql */
$write = Mage::getSingleton('core/resource')->getConnection('core_write');

$sql = "
SELECT count(*) FROM information_schema.COLUMNS
WHERE COLUMN_NAME='id' AND TABLE_NAME='{$tableName}' AND TABLE_SCHEMA='{$dbname}';
";

$result = $write->fetchOne($sql);

if($result == 0) {
    $installer->run("
		ALTER TABLE `{$tableName}`
			DROP PRIMARY KEY;

		ALTER TABLE `{$tableName}`
			ADD COLUMN `id` INT(10) NOT NULL AUTO_INCREMENT COMMENT 'id' FIRST,
			ADD PRIMARY KEY (`id`);
	");
}

$installer->endSetup();