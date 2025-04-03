<?php

use yii\db\Migration;

class m240219_000006_add_updated_at_to_feedback_buyer extends Migration
{
    public function safeUp()
    {
        $this->addColumn('feedback_buyer', 'updated_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
    }

    public function safeDown()
    {
        $this->dropColumn('feedback_buyer', 'updated_at');
    }
} 