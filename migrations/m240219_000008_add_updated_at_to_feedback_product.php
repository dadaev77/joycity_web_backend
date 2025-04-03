<?php

use yii\db\Migration;

class m240219_000008_add_updated_at_to_feedback_product extends Migration
{
    public function safeUp()
    {
        $table = Yii::$app->db->schema->getTableSchema('feedback_product');
        
        if (!isset($table->columns['updated_at'])) {
            $this->addColumn('feedback_product', 'updated_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
        }
    }

    public function safeDown()
    {
        $table = Yii::$app->db->schema->getTableSchema('feedback_product');
        
        if (isset($table->columns['updated_at'])) {
            $this->dropColumn('feedback_product', 'updated_at');
        }
    }
} 