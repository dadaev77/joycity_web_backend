<?php

use yii\db\Migration;

class m240219_000011_add_timestamps_to_feedback_user_link_attachment extends Migration
{
    public function safeUp()
    {
        $table = Yii::$app->db->schema->getTableSchema('feedback_user_link_attachment');
        
        if (!isset($table->columns['created_at'])) {
            $this->addColumn('feedback_user_link_attachment', 'created_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
        }
        
        if (!isset($table->columns['updated_at'])) {
            $this->addColumn('feedback_user_link_attachment', 'updated_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
        }
    }

    public function safeDown()
    {
        $table = Yii::$app->db->schema->getTableSchema('feedback_user_link_attachment');
        
        if (isset($table->columns['created_at'])) {
            $this->dropColumn('feedback_user_link_attachment', 'created_at');
        }
        
        if (isset($table->columns['updated_at'])) {
            $this->dropColumn('feedback_user_link_attachment', 'updated_at');
        }
    }
} 