<?php

use yii\db\Migration;

class m240219_000012_add_updated_at_to_fulfillment_inspection_report extends Migration
{
    public function safeUp()
    {
        $table = Yii::$app->db->schema->getTableSchema('fulfillment_inspection_report');
        
        if (!isset($table->columns['updated_at'])) {
            $this->addColumn('fulfillment_inspection_report', 'updated_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
        }
    }

    public function safeDown()
    {
        $table = Yii::$app->db->schema->getTableSchema('fulfillment_inspection_report');
        
        if (isset($table->columns['updated_at'])) {
            $this->dropColumn('fulfillment_inspection_report', 'updated_at');
        }
    }
} 