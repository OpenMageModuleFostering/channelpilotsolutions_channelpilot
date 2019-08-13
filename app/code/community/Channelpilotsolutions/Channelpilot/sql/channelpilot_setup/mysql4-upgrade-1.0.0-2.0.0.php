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

/**
 * Create table 'channelpilot/registration'
 */
$table = $adapter->newTable($installer->getTable('channelpilot/registration'))
    ->addColumn('shopId', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => false,
        'primary' => true
    ), 'shopId')
    ->addColumn('ips_authorized', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => false
    ), 'ips_authorized')
    ->addColumn('merchantId', Varien_Db_Ddl_Table::TYPE_VARCHAR, 150, array(
        'nullable' => false
    ), 'merchantId')
    ->addColumn('securityToken', Varien_Db_Ddl_Table::TYPE_VARCHAR, 150, array(
        'nullable' => false
    ), 'securityToken')
    ->addColumn('last_stock_update', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable' => true
    ), 'last_stock_update')
    ->addColumn('last_price_update', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable' => true
    ), 'last_price_update')
    ->addColumn('last_catalog_update', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable' => true
    ), 'last_catalog_update')
    ->addIndex(
        $installer->getIdxName(
            $installer->getTable('channelpilot/registration'), array('merchantId', 'securityToken'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ), array('merchantId', 'securityToken'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    );
$adapter->createTable($table);

/**
 * Create table 'channelpilot/order'
 */
$table = $adapter->newTable($installer->getTable('channelpilot/order'))
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => false,
        'primary' => true
    ), 'order_id')
    ->addColumn('order_nr', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => false
    ), 'order_nr')
    ->addColumn('marketplace_order_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 150, array(
        'nullable' => false
    ), 'marketplace_order_id')
    ->addColumn('marketplace', Varien_Db_Ddl_Table::TYPE_VARCHAR, 150, array(
        'nullable' => false
    ), 'marketplace')
    ->addColumn('shop', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => false
    ), 'shop')
    ->addColumn('created', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT,
        'nullable' => false
    ), 'created')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
        'nullable' => true
    ), 'status')
    ->addIndex($installer->getIdxName(
        'channelpilot/order', array('order_nr'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
    ), array('order_nr'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    )
    ->addIndex(
        $installer->getIdxName(
            $installer->getTable('channelpilot/order'), array('marketplace_order_id', 'marketplace'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ), array('marketplace_order_id', 'marketplace'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    );
$adapter->createTable($table);

/**
 * Create table 'channelpilot/order_item'
 */
$table = $adapter->newTable($installer->getTable('channelpilot/order_item'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'auto_increment' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
    ), 'id')
    ->addColumn('order_item_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => false,
    ), 'order_item_id')
    ->addColumn('marketplace_order_item_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => false
    ), 'marketplace_order_item_id')
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => false
    ), 'order_id')
    ->addColumn('cancelled', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'default' => 0,
        'nullable' => false
    ), 'cancelled')
    ->addColumn('amount', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'default' => 0,
        'nullable' => false
    ), 'amount')
    ->addColumn('amount_delivered', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'default' => 0,
        'nullable' => false
    ), 'amount_delivered')
    ->addIndex($installer->getIdxName(
        'channelpilot/order_item', array('order_item_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
    ), array('order_item_id'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    )
    ->addIndex($installer->getIdxName(
        'channelpilot/order_item', array('marketplace_order_item_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
    ), array('marketplace_order_item_id'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    )
    ->addIndex($installer->getIdxName(
        $installer->getTable('channelpilot/order_item'), array('marketplace_order_item_id', 'order_item_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
    ), array('marketplace_order_item_id', 'order_item_id'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    );
$adapter->createTable($table);

/**
 * Create table 'channelpilot/prices'
 */
$table = $adapter->newTable($installer->getTable('channelpilot/prices'))
    ->addColumn('price_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => false,
        'primary' => true
    ), 'price_id')
    ->addColumn('last_price_update', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable' => true
    ), 'last_price_update');
$adapter->createTable($table);

/**
 * Create table 'channelpilot/logs'
 */
$table = $adapter->newTable($installer->getTable('channelpilot/logs'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'auto_increment' => true,
        'nullable' => false,
        'primary' => true
    ), 'id')
    ->addColumn('created', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT,
        'nullable' => false
    ), 'created')
    ->addColumn('content', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
        'nullable' => false
    ), 'content')
    ->addIndex($installer->getIdxName(
        'channelpilot/logs', array('created')
    ), array('created'));
$adapter->createTable($table);

$installer->endSetup();