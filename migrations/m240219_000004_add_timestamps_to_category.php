<?php

use yii\db\Migration;

class m240219_000004_add_timestamps_to_category extends Migration
{
    public function safeUp()
    {
        $this->addColumn('category', 'created_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
        $this->addColumn('category', 'updated_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
    }

    public function safeDown()
    {
        $this->dropColumn('category', 'created_at');
        $this->dropColumn('category', 'updated_at');
    }
} 