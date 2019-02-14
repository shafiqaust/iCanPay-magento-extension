<?php

$installer = $this;
$installer->startSetup();
$table = $installer->getConnection()
		->newTable($installer->getTable('mageapps_icanpay/icanpay'))
		->addColumn('icanpay_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Id')
        ->addColumn('api_type', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable'  => false,
        ), 'Api Type')
        ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable'  => false,
        ), 'Order Id')
        ->addColumn('amount', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable'  => false,
        ), 'Amount')
        ->addColumn('transaction_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable'  => false,
        ), 'Transaction Id')
        ->addColumn('order_customer_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable'  => false,
        ), 'Customer Id')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null,
        array('nullable' => false,
        ),'Created At');
 $installer->getConnection()->createTable($table);
$installer->endSetup();
