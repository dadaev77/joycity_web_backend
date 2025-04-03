<?php

use yii\db\Migration;

class m240219_000014_add_updated_at_to_multiple_tables extends Migration
{
    public function safeUp()
    {
        $tables = [
            'fulfillment_offer',
            'fulfillment_packaging_labeling',
            'fulfillment_stock_report',
            'notification',
            'order',
            'order_tracking',
            'product_inspection_report',
            'product_stock_report',
            'rate',
            'user_verification_request',
            'waybill'
        ];

        foreach ($tables as $table) {
            $tableSchema = Yii::$app->db->schema->getTableSchema($table);
            if (!isset($tableSchema->columns['updated_at'])) {
                $this->addColumn($table, 'updated_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
            }
        }
    }

    public function safeDown()
    {
        $tables = [
            'fulfillment_offer',
            'fulfillment_packaging_labeling',
            'fulfillment_stock_report',
            'notification',
            'order',
            'order_tracking',
            'product_inspection_report',
            'product_stock_report',
            'rate',
            'user_verification_request',
            'waybill'
        ];

        foreach ($tables as $table) {
            $tableSchema = Yii::$app->db->schema->getTableSchema($table);
            if (isset($tableSchema->columns['updated_at'])) {
                $this->dropColumn($table, 'updated_at');
            }
        }
    }
} 