<?php

use yii\db\Migration;

class m240219_000010_add_updated_at_to_feedback_user extends Migration
{
    public function safeUp()
    {
        $table = Yii::$app->db->schema->getTableSchema('feedback_user');
        
        if (!isset($table->columns['updated_at'])) {
            $this->addColumn('feedback_user', 'updated_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
        }
    }

    public function safeDown()
    {
        $table = Yii::$app->db->schema->getTableSchema('feedback_user');
        
        if (isset($table->columns['updated_at'])) {
            $this->dropColumn('feedback_user', 'updated_at');
        }
    }
} 