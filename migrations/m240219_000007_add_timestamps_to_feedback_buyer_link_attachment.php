<?php

use yii\db\Migration;

class m240219_000007_add_timestamps_to_feedback_buyer_link_attachment extends Migration
{
    public function safeUp()
    {
        $this->addColumn('feedback_buyer_link_attachment', 'created_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
        $this->addColumn('feedback_buyer_link_attachment', 'updated_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
    }

    public function safeDown()
    {
        $this->dropColumn('feedback_buyer_link_attachment', 'created_at');
        $this->dropColumn('feedback_buyer_link_attachment', 'updated_at');
    }
} 