<?php

use yii\db\Migration;

/**
 * Class m240108_224753_create_fulfillment_packaging_labeling_tables
 */
class m240108_224753_create_fulfillment_packaging_labeling_tables extends
    Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('fulfillment_packaging_labeling', [
            'id' => $this->primaryKey(),
            'created_at' => $this->dateTime()->notNull(),
            'order_id' => $this->integer()->notNull(),
            'fulfillment_id' => $this->integer()->notNull(),
            'shipped_product' => $this->integer()
                ->defaultValue(0)
                ->notNull(),
        ]);

        $this->addForeignKey(
            'fk-fulfillment_packaging_labeling-order_id',
            'fulfillment_packaging_labeling',
            'order_id',
            'order',
            'id',
        );

        $this->createTable('packaging_report_link_attachment', [
            'id' => $this->primaryKey(),
            'packaging_report_id' => $this->integer()->notNull(),
            'attachment_id' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-packaging_report_link_attachment-packaging_report_id',
            'packaging_report_link_attachment',
            'packaging_report_id',
            'fulfillment_packaging_labeling',
            'id',
        );

        $this->addForeignKey(
            'fk-packaging_report_link_attachment-attachment_id',
            'packaging_report_link_attachment',
            'attachment_id',
            'attachment',
            'id',
        );

        $this->createIndex(
            'idx-packaging_report_link_attachment-packaging_report_id',
            'packaging_report_link_attachment',
            'packaging_report_id',
        );

        $this->addForeignKey(
            'fk_fulfillment_packaging_labeling_user',
            'fulfillment_packaging_labeling',
            'fulfillment_id',
            'user',
            'id',
        );
    }
}
