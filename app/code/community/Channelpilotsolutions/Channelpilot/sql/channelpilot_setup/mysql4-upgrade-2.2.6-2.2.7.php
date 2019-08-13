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
$adapter = $installer->getConnection();

/**
 * Change the table 'channelpilot/registration'
 */

$tableName = $installer->getTable('channelpilot/registration');

$installer->run("
ALTER TABLE `{$tableName}`
	DROP PRIMARY KEY,
	DROP INDEX `UNQ_CP_REGISTRATION_MERCHANTID_SECURITYTOKEN`,
	ADD COLUMN `id` INT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
	ADD PRIMARY KEY (`id`),
	ADD UNIQUE INDEX `UNQ_CP_REGISTRATION_SHOPID_MERCHANTID_SECURITYTOKEN` (`shopId`, `merchantId`, `securityToken`);
");

$installer->endSetup();