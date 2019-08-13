<?php

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


/**
CREATE TABLE IF NOT EXISTS `cp_registration` (
  `shopId` varchar(255) NOT NULL,
  `ips_authorized` varchar(255) DEFAULT NULL,
  `merchantId` varchar(150) DEFAULT NULL,
  `securityToken` varchar(150) DEFAULT NULL,
  `last_stock_update` timestamp NULL DEFAULT NULL,
  `last_price_update` timestamp NULL DEFAULT NULL,
  `last_catalog_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`shopId`),
  UNIQUE KEY `cp_registration_merchantId_securityToken` (`merchantId`,`securityToken`)
);

CREATE TABLE IF NOT EXISTS `cp_marketplace_orders` (
  `order_id` varchar(255) NOT NULL,
  `order_nr` varchar(255) NOT NULL,
  `marketplace_order_id` varchar(150) NOT NULL,
  `marketplace` varchar(150) NOT NULL,
  `shop` varchar(255) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` int(10) DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `cp_marketplace_orders_marketplace_order_id_marketplace` (`marketplace_order_id`,`marketplace`),
  UNIQUE KEY `cp_marketplace_orders_order_nr` (`order_nr`)
);

CREATE TABLE IF NOT EXISTS `cp_marketplace_order_items` (
  `order_item_id` varchar(255) NOT NULL,
  `marketplace_order_item_id` varchar(255) NOT NULL,
  `order_id` varchar(255) NOT NULL,
  `cancelled` int(11) NOT NULL DEFAULT '0',
  `amount` int(11) NOT NULL DEFAULT '0',
  `amount_delivered` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `cp_marketplace_order_items_order_item_id` (`order_item_id`),
  UNIQUE KEY `cp_marketplace_order_items_marketplace_order_item_id` (`marketplace_order_item_id`)
);

CREATE TABLE IF NOT EXISTS `cp_logging` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `content` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cp_logging_created` (`created`)
);
 */