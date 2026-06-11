<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Maho_NetsEasy
 */

declare(strict_types=1);

/** @var Mage_Core_Model_Resource_Setup $this */
$installer = $this;
$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('netseasy/payment'))
    ->addColumn('entity_id', Maho\Db\Ddl\Table::TYPE_INTEGER, null, [
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary'  => true,
    ], 'Entity ID')
    ->addColumn('payment_id', Maho\Db\Ddl\Table::TYPE_VARCHAR, 64, [
        'nullable' => false,
    ], 'Nets Payment ID')
    ->addColumn('quote_id', Maho\Db\Ddl\Table::TYPE_INTEGER, null, [
        'unsigned' => true,
        'nullable' => false,
    ], 'Quote ID')
    ->addColumn('order_id', Maho\Db\Ddl\Table::TYPE_INTEGER, null, [
        'unsigned' => true,
        'nullable' => true,
    ], 'Order ID')
    ->addColumn('checkout_flow', Maho\Db\Ddl\Table::TYPE_VARCHAR, 20, [
        'nullable' => false,
        'default'  => 'embedded',
    ], 'Checkout Flow (embedded or hosted)')
    ->addColumn('created_at', Maho\Db\Ddl\Table::TYPE_TIMESTAMP, null, [
        'nullable' => false,
        'default'  => Maho\Db\Ddl\Table::TIMESTAMP_INIT,
    ], 'Created At')
    ->addIndex(
        $installer->getIdxName('netseasy/payment', ['payment_id'], Maho\Db\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE),
        ['payment_id'],
        ['type' => Maho\Db\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE],
    )
    ->addIndex(
        $installer->getIdxName('netseasy/payment', ['quote_id']),
        ['quote_id'],
    )
    ->addIndex(
        $installer->getIdxName('netseasy/payment', ['order_id']),
        ['order_id'],
    )
    ->setComment('Nets Easy Payment Tracking');

$installer->getConnection()->createTable($table);

$installer->endSetup();
