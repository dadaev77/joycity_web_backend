<?php

use yii\db\Migration;

class m240219_000001_add_timestamps_to_attachment extends Migration
{
    public function safeUp()
    {
        $this->addColumn('attachment', 'created_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
        $this->addColumn('attachment', 'updated_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
    }

    public function safeDown()
    {
        $this->dropColumn('attachment', 'created_at');
        $this->dropColumn('attachment', 'updated_at');
    }
} 