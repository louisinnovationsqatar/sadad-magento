<?php
// Built by Louis Innovations (www.louis-innovations.com)

declare(strict_types=1);

namespace LouisInnovations\Sadad\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Creates the sadad_transactions table to store SADAD payment records.
 *
 * This table acts as a local audit log of all SADAD interactions,
 * supplementing the Magento sales_payment_transaction table.
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $installer = $setup;
        $installer->startSetup();

        $this->createSadadTransactionsTable($installer);

        $installer->endSetup();
    }

    /**
     * @throws \Zend_Db_Exception
     */
    private function createSadadTransactionsTable(SchemaSetupInterface $installer): void
    {
        $tableName = $installer->getTable('sadad_transactions');

        if ($installer->tableExists('sadad_transactions')) {
            return;
        }

        $table = $installer->getConnection()
            ->newTable($tableName)

            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'nullable' => false,
                    'primary'  => true,
                    'unsigned' => true,
                ],
                'Primary Key'
            )

            ->addColumn(
                'order_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => true, 'unsigned' => true],
                'Magento Order ID (sales_order.entity_id)'
            )

            ->addColumn(
                'increment_id',
                Table::TYPE_TEXT,
                32,
                ['nullable' => false, 'default' => ''],
                'Magento Order Increment ID'
            )

            ->addColumn(
                'sadad_order_id',
                Table::TYPE_TEXT,
                64,
                ['nullable' => false, 'default' => ''],
                'SADAD ORDER_ID (with prefix) sent to gateway'
            )

            ->addColumn(
                'transaction_number',
                Table::TYPE_TEXT,
                128,
                ['nullable' => false, 'default' => ''],
                'SADAD Transaction Number returned by gateway'
            )

            ->addColumn(
                'invoice_number',
                Table::TYPE_TEXT,
                128,
                ['nullable' => false, 'default' => ''],
                'SADAD Invoice Number (if applicable)'
            )

            ->addColumn(
                'amount',
                Table::TYPE_DECIMAL,
                '12,4',
                ['nullable' => false, 'default' => '0.0000'],
                'Transaction Amount'
            )

            ->addColumn(
                'currency_code',
                Table::TYPE_TEXT,
                8,
                ['nullable' => false, 'default' => 'QAR'],
                'Currency Code'
            )

            ->addColumn(
                'checkout_mode',
                Table::TYPE_TEXT,
                8,
                ['nullable' => false, 'default' => 'v1.1'],
                'Checkout Mode (v1.1 / v2.1 / v2.2)'
            )

            ->addColumn(
                'environment',
                Table::TYPE_TEXT,
                8,
                ['nullable' => false, 'default' => 'test'],
                'Environment (test / live)'
            )

            ->addColumn(
                'status',
                Table::TYPE_TEXT,
                32,
                ['nullable' => false, 'default' => 'pending'],
                'Transaction Status'
            )

            ->addColumn(
                'response_code',
                Table::TYPE_TEXT,
                16,
                ['nullable' => false, 'default' => ''],
                'SADAD Response Code'
            )

            ->addColumn(
                'response_message',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'SADAD Response Message'
            )

            ->addColumn(
                'is_test_mode',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '1', 'unsigned' => true],
                'Is Test Mode (1 = test, 0 = live)'
            )

            ->addColumn(
                'is_refunded',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '0', 'unsigned' => true],
                'Is Refunded (1 = yes)'
            )

            ->addColumn(
                'refunded_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true, 'default' => null],
                'Refund Timestamp'
            )

            ->addColumn(
                'webhook_received_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true, 'default' => null],
                'Webhook Received Timestamp'
            )

            ->addColumn(
                'raw_payload',
                Table::TYPE_TEXT,
                '64k',
                ['nullable' => false, 'default' => ''],
                'Raw JSON payload from SADAD (webhook or callback)'
            )

            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Created At'
            )

            ->addColumn(
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                'Updated At'
            )

            ->addIndex(
                $installer->getIdxName('sadad_transactions', ['order_id']),
                ['order_id']
            )

            ->addIndex(
                $installer->getIdxName('sadad_transactions', ['increment_id']),
                ['increment_id']
            )

            ->addIndex(
                $installer->getIdxName('sadad_transactions', ['transaction_number']),
                ['transaction_number']
            )

            ->addIndex(
                $installer->getIdxName(
                    'sadad_transactions',
                    ['sadad_order_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['sadad_order_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )

            ->addForeignKey(
                $installer->getFkName(
                    'sadad_transactions',
                    'order_id',
                    'sales_order',
                    'entity_id'
                ),
                'order_id',
                $installer->getTable('sales_order'),
                'entity_id',
                Table::ACTION_SET_NULL
            )

            ->setComment('SADAD Payment Transaction Log');

        $installer->getConnection()->createTable($table);
    }
}
