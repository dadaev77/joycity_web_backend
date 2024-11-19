<?php

use yii\db\Migration;

/**
 * Class m240130_162345_fulfillment_marketplace_transaction
 */
class m240130_162345_fulfillment_marketplace_transaction extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('fulfillment_marketplace_transaction', [
            'id' => $this->primaryKey(),
            'created_at' => $this->dateTime()->notNull(),
            'fulfillment_id' => $this->integer()->notNull(),
            'order_id' => $this->integer()->notNull(),
            'product_count' => $this->integer()->notNull(),
            'amount' => $this->decimal(10, 4)->notNull(),
            'status' => $this->string()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_fulfillment_marketplace_transaction_fulfillment',
            'fulfillment_marketplace_transaction',
            'fulfillment_id',
            'user',
            'id',
        );

        $this->addForeignKey(
            'fk_fulfillment_marketplace_transaction_order',
            'fulfillment_marketplace_transaction',
            'order_id',
            'order',
            'id',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey(
            'fk_fulfillment_marketplace_transaction_fulfillment',
            'fulfillment_marketplace_transaction',
        );

        $this->dropForeignKey(
            'fk_fulfillment_marketplace_transaction_order',
            'fulfillment_marketplace_transaction',
        );

        $this->dropTable('fulfillment_marketplace_transaction');
    }
}
