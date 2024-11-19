<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%product_stock_report_link_attachment}}`.
 */
class m230914_152745_create_product_stock_report_link_attachment_table extends
    Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('product_stock_report_link_attachment', [
            'id' => $this->primaryKey(),
            'product_stock_report' => $this->integer()->notNull(),
            'attachment_id' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'idx-product_stock_report_link_attachment-product_stock_report',
            'product_stock_report_link_attachment',
            'product_stock_report'
        );

        $this->createIndex(
            'idx-product_stock_report_link_attachment-attachment_id',
            'product_stock_report_link_attachment',
            'attachment_id'
        );

        $this->addForeignKey(
            'fk-product_stock_report_link_attachment-product_stock_report',
            'product_stock_report_link_attachment',
            'product_stock_report',
            'product_stock_report',
            'id',
            'NO ACTION',
            'NO ACTION'
        );

        $this->addForeignKey(
            'fk-product_stock_report_link_attachment-attachment_id',
            'product_stock_report_link_attachment',
            'attachment_id',
            'attachment',
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
        $this->dropTable('{{%product_stock_report_link_attachment}}');
    }
}
