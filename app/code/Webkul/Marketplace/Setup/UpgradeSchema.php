<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) 2010-2016 Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\Marketplace\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        /**
         * Update tables 'marketplace_saleperpartner'
         */
        $setup->getConnection()->changeColumn(
            $setup->getTable('marketplace_saleperpartner'),
            'commission_rate',
            'commission_rate',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'comment' => 'Commission Rate'
            ]
        );

        /**
         * Update tables 'marketplace_saleslist'
         */
        $setup->getConnection()->addColumn(
            $setup->getTable('marketplace_saleslist'),
            'is_shipping',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '0',
                'comment' => 'Is Shipping Applied'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('marketplace_saleslist'),
            'is_coupon',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '0',
                'comment' => 'Is Coupon Applied'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('marketplace_saleslist'),
            'is_paid',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '0',
                'comment' => 'Is seller paid for current row'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('marketplace_saleslist'),
            'commission_rate',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'nullable' => false,
                'default' => '0',
                'comment' => 'Commission Rate applied at the time of order placed'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('marketplace_saleslist'),
            'applied_coupon_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'nullable' => false,
                'default' => '0',
                'comment' => 'Applied coupon amount at the time of order placed'
            ]
        );
        /**
         * Update tables 'marketplace_orders'
         */
        $setup->getConnection()->addColumn(
            $setup->getTable('marketplace_orders'),
            'tax_to_seller',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '0',
                'comment' => 'Tax to seller account flag'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('marketplace_orders'),
            'total_tax',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'nullable' => false,
                'default' => '0',
                'comment' => 'Total Tax'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('marketplace_orders'),
            'coupon_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'nullable' => false,
                'default' => '0',
                'comment' => 'Coupon Amount'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('marketplace_orders'),
            'refunded_coupon_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'nullable' => false,
                'default' => '0',
                'comment' => 'Refunded Coupon Amount'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('marketplace_orders'),
            'refunded_shipping_charges',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'nullable' => false,
                'default' => '0',
                'comment' => 'Refunded Shipping Amount'
            ]
        );

        $setup->endSetup();
    }
}
