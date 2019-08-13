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

/** @var $adapter Varien_Db_Adapter_Pdo_Mysql */
$adapter = $installer->getConnection();
$tableName = Mage::getSingleton('core/resource')->getTableName('channelpilot/order');
$adapter->addColumn($tableName, 'order_paid', array(
    'TYPE'          => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'UNSIGNED'      => true,
    'DEFAULT'       => 0,
    'NULLABLE'      => false,
    'COMMENT'       => 'order is paid status flag',
));

/**
 * Create table 'channelpilot/order_shipment'
 */
$table = $adapter->newTable($installer->getTable('channelpilot/order_shipment'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'auto_increment' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
    ), 'id')
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'nullable' => false,
        'unsigned' => true,
    ), 'order_id')
    ->addColumn('shipment_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'nullable' => false,
        'unsigned' => true,
    ), 'shipment_id');
$adapter->createTable($table);

$installer->endSetup();