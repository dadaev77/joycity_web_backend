<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%fulfillment_inspection_report}}`.
 */
class m240107_013654_create_fulfillment_inspection_report_table extends
    Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('fulfillment_inspection_report', [
            'id' => $this->primaryKey(),
            'created_at' => $this->dateTime()->notNull(),
            'order_id' => $this->integer()->notNull(),
            'defects_count' => $this->integer()
                ->defaultValue(0)
                ->notNull(),
            'package_state' => $this->string(255)->notNull(),
            'is_deep' => $this->tinyInteger()
                ->defaultValue(0)
                ->notNull(),
            'fulfillment_id' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_fulfillment_inspection_report_user',
            'fulfillment_inspection_report',
            'fulfillment_id',
            'user',
            'id',
        );

        $this->addForeignKey(
            'fk-fulfillment_inspection_report-order_id',
            'fulfillment_inspection_report',
            'order_id',
            'order',
            'id',
        );

        $this->createIndex(
            'idx-fulfillment_inspection_report-order_id',
            'fulfillment_inspection_report',
            'order_id',
        );

        $this->createTable('fulfillment_stock_report', [
            'id' => $this->primaryKey(),
            'created_at' => $this->dateTime()->notNull(),
            'order_id' => $this->integer()->notNull(),
            'fulfillment_id' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_fulfillment_stock_report',
            'fulfillment_stock_report',
            'fulfillment_id',
            'user',
            'id',
        );

        $this->addForeignKey(
            'fk-fulfillment_stock_report-order_id',
            'fulfillment_stock_report',
            'order_id',
            'order',
            'id',
        );

        $this->createIndex(
            'idx-fulfillment_stock_report-order_id',
            'fulfillment_stock_report',
            'order_id',
        );

        $this->createTable('fulfillment_stock_report_link_attachment', [
            'id' => $this->primaryKey(),
            'fulfillment_stock_report_id' => $this->integer()->notNull(),
            'attachment_id' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-fulfillment_stock_report_link_attachment-attachment_id',
            'fulfillment_stock_report_link_attachment',
            'attachment_id',
            'attachment',
            'id',
        );

        $this->addForeignKey(
            'fk-fulfillment_stock_report_link_attachment_stock_report',
            'fulfillment_stock_report_link_attachment',
            'fulfillment_stock_report_id',
            'fulfillment_stock_report',
            'id',
        );

        $this->createIndex(
            'idx-fulfillment_stock_report_link_attachment-attachment_id',
            'fulfillment_stock_report_link_attachment',
            'attachment_id',
        );

        $this->createIndex(
            'idx-fulfillment_stock_report_link_attachment_stock_report',
            'fulfillment_stock_report_link_attachment',
            'fulfillment_stock_report_id',
        );
    }
}
