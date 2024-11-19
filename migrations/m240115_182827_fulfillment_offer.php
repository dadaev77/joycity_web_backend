<?php

use yii\db\Migration;

/**
 * Class m240115_182827_fulfillment_offer
 */
class m240115_182827_fulfillment_offer extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('fulfillment_offer', [
            'id' => $this->primaryKey(),
            'created_at' => $this->dateTime()->notNull(),
            'order_id' => $this->integer()->notNull(),
            'fulfillment_id' => $this->integer()->notNull(),
            'status' => $this->string()->notNull(),
            'overall_price' => $this->decimal(10, 4)->notNull(),
        ]);

        $this->createIndex(
            'fk_fulfillment_offer_order_request_idx',
            'fulfillment_offer',
            'order_id',
            true,
        );
        $this->createIndex(
            'fk_fulfillment_offer_user_idx',
            'fulfillment_offer',
            'fulfillment_id',
        );
        $this->addForeignKey(
            'fk_fulfillment_offer_order_id',
            'fulfillment_offer',
            'order_id',
            'order',
            'id',
        );
        $this->addForeignKey(
            'fk_fulfillment_offer_user',
            'fulfillment_offer',
            'fulfillment_id',
            'user',
            'id',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey(
            'fk_fulfillment_offer_order_id',
            'fulfillment_offer',
        );
        $this->dropForeignKey('fk_fulfillment_offer_user', 'fulfillment_offer');
        $this->dropIndex(
            'fk_fulfillment_offer_order_request_idx',
            'fulfillment_offer',
        );
        $this->dropIndex('fk_fulfillment_offer_user_idx', 'fulfillment_offer');
        $this->dropTable('fulfillment_offer');
    }
}
