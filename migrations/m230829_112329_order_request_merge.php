<?php

use yii\db\Migration;

/**
 * Class m230829_112329_order_request_merge
 */
class m230829_112329_order_request_merge extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->migrateOrderRequest();
        $this->migrateBuyerOffer();

        $this->createTable('order_link_attachment', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'attachment_id' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_order_link_attachment_order_id',
            'order_link_attachment',
            'order_id',
            'order',
            'id'
        );
        $this->addForeignKey(
            'fk_order_link_attachment_attachment_id',
            'order_link_attachment',
            'attachment_id',
            'attachment',
            'id'
        );

        $this->dropTable('order_request_link_attachment');
        $this->dropTable('order_request');
    }

    public function migrateOrderRequest()
    {
        $this->addColumn(
            'order',
            'created_at',
            $this->dateTime()
                ->notNull()
                ->after('id')
        );

        $this->renameColumn('order', 'customer_id', 'created_by');
        $this->alterColumn(
            'order',
            'created_by',
            $this->integer()
                ->notNull()
                ->after('status')
        );

        $this->alterColumn(
            'order',
            'buyer_id',
            $this->integer()->after('created_by')
        );

        $this->alterColumn('order', 'product_id', $this->integer());

        $this->dropColumn('order', 'price');
        $this->dropColumn('order', 'delivery_type');
        $this->dropColumn('order', 'delivery_point_address');
        $this->dropColumn('order', 'package_type');
        $this->dropColumn('order', 'deep_inspection');
        $this->dropColumn('order', 'inspection_report');
        $this->dropColumn('order', 'in_stock_report');
        $this->dropColumn('order', 'delivery_report');
        $this->dropColumn('order', 'delivery_point_type');

        $this->addColumn(
            'order',
            'subcategory_id',
            $this->integer()->notNull()
        );
        $this->addColumn(
            'order',
            'type_packaging_id',
            $this->integer()->notNull()
        );
        $this->addColumn(
            'order',
            'type_delivery_id',
            $this->integer()->notNull()
        );
        $this->addColumn(
            'order',
            'type_delivery_point_id',
            $this->integer()->notNull()
        );
        $this->addColumn(
            'order',
            'delivery_point_address_id',
            $this->integer()->notNull()
        );
        $this->addColumn(
            'order',
            'price_product',
            $this->float()
                ->notNull()
                ->defaultValue(0)
        );
        $this->addColumn(
            'order',
            'price_inspection',
            $this->float()
                ->notNull()
                ->defaultValue(0)
        );
        $this->addColumn(
            'order',
            'price_packaging',
            $this->float()
                ->notNull()
                ->defaultValue(0)
        );
        $this->addColumn(
            'order',
            'price_fulfilment',
            $this->float()
                ->notNull()
                ->defaultValue(0)
        );
        $this->addColumn(
            'order',
            'price_delivery',
            $this->float()
                ->notNull()
                ->defaultValue(0)
        );
        $this->addColumn(
            'order',
            'is_need_deep_inspection',
            $this->boolean()
                ->notNull()
                ->defaultValue(0)
        );

        $this->addForeignKey(
            'fk_order_subcategory_id',
            'order',
            'subcategory_id',
            'subcategory',
            'id'
        );
        $this->addForeignKey(
            'fk_order_type_packaging_id',
            'order',
            'type_packaging_id',
            'type_packaging',
            'id'
        );
        $this->addForeignKey(
            'fk_order_type_delivery_id',
            'order',
            'type_delivery_id',
            'type_delivery',
            'id'
        );
        $this->addForeignKey(
            'fk_order_type_delivery_point_id',
            'order',
            'type_delivery_point_id',
            'type_delivery_point',
            'id'
        );
        $this->addForeignKey(
            'fk_order_delivery_point_address_id',
            'order',
            'delivery_point_address_id',
            'delivery_point_address',
            'id'
        );
    }

    public function migrateBuyerOffer()
    {
        $this->renameColumn('buyer_offer', 'date_time', 'created_at');
        $this->alterColumn(
            'buyer_offer',
            'created_at',
            $this->dateTime()
                ->notNull()
                ->after('id')
        );

        $this->renameColumn('buyer_offer', 'order_request_id', 'order_id');

        $this->dropColumn('buyer_offer', 'quantity');
        $this->dropColumn('buyer_offer', 'price');

        $this->addColumn('buyer_offer', 'status', $this->integer()->notNull());

        $this->addColumn(
            'buyer_offer',
            'price_product',
            $this->float()->notNull()
        );
        $this->addColumn(
            'buyer_offer',
            'price_inspection',
            $this->float()->notNull()
        );
        $this->addColumn(
            'buyer_offer',
            'price_packaging',
            $this->float()->notNull()
        );
        $this->addColumn(
            'buyer_offer',
            'price_fulfilment',
            $this->float()->notNull()
        );
        $this->addColumn(
            'buyer_offer',
            'price_delivery',
            $this->float()->notNull()
        );

        $this->dropForeignKey('fk_buyer_offer_order_request1', 'buyer_offer');
        $this->addForeignKey(
            'fk_buyer_offer_order_id',
            'buyer_offer',
            'order_id',
            'order',
            'id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230829_112329_order_request_merge cannot be reverted.\n";

        return false;
    }
}
