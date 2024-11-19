<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%product_stock_report}}`.
 */
class m230914_145031_create_product_stock_report_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('product_stock_report', [
            'id' => $this->primaryKey(),
            'created_at' => $this->dateTime()->notNull(),
            'order_id' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'idx-product_stock_report-order_id',
            'product_stock_report',
            'order_id'
        );

        $this->addForeignKey(
            'fk-product_stock_report-order_id',
            'product_stock_report',
            'order_id',
            'order',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%product_stock_report}}');
    }
}
