<?php

use yii\db\Migration;

class m240219_000015_add_timestamps_to_multiple_tables extends Migration
{
    public function safeUp()
    {
        $tables = [
            'fulfillment_stock_report_link_attachment',
            'heartbeat',
            'migration',
            'order_distribution',
            'order_link_attachment',
            'order_rate',
            'packaging_report_link_attachment',
            'privacy_policy',
            'product',
            'product_link_attachment',
            'product_stock_report_link_attachment',
            'push_notification',
            'queue',
            'type_delivery',
            'type_delivery_link_category',
            'type_delivery_point',
            'type_delivery_price',
            'type_packaging',
            'user',
            'user_link_category',
            'user_link_type_delivery',
            'user_link_type_packaging',
            'user_settings'
        ];

        foreach ($tables as $table) {
            $tableSchema = Yii::$app->db->schema->getTableSchema($table);
            
            if (!isset($tableSchema->columns['created_at'])) {
                $this->addColumn($table, 'created_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
            }
            
            if (!isset($tableSchema->columns['updated_at'])) {
                $this->addColumn($table, 'updated_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
            }
        }
    }

    public function safeDown()
    {
        $tables = [
            'fulfillment_stock_report_link_attachment',
            'heartbeat',
            'migration',
            'order_distribution',
            'order_link_attachment',
            'order_rate',
            'packaging_report_link_attachment',
            'privacy_policy',
            'product',
            'product_link_attachment',
            'product_stock_report_link_attachment',
            'push_notification',
            'queue',
            'type_delivery',
            'type_delivery_link_category',
            'type_delivery_point',
            'type_delivery_price',
            'type_packaging',
            'user',
            'user_link_category',
            'user_link_type_delivery',
            'user_link_type_packaging',
            'user_settings'
        ];

        foreach ($tables as $table) {
            $tableSchema = Yii::$app->db->schema->getTableSchema($table);
            
            if (isset($tableSchema->columns['created_at'])) {
                $this->dropColumn($table, 'created_at');
            }
            
            if (isset($tableSchema->columns['updated_at'])) {
                $this->dropColumn($table, 'updated_at');
            }
        }
    }
} 