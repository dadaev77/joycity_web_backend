<?php

use yii\db\Migration;

class m240219_000013_add_updated_at_to_fulfillment_marketplace_transaction extends Migration
{
    public function safeUp()
    {
        $table = Yii::$app->db->schema->getTableSchema('fulfillment_marketplace_transaction');
        
        if (!isset($table->columns['updated_at'])) {
            $this->addColumn('fulfillment_marketplace_transaction', 'updated_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
        }
    }

    public function safeDown()
    {
        $table = Yii::$app->db->schema->getTableSchema('fulfillment_marketplace_transaction');
        
        if (isset($table->columns['updated_at'])) {
            $this->dropColumn('fulfillment_marketplace_transaction', 'updated_at');
        }
    }
} 