<?php

use yii\db\Migration;

class m240219_000000_add_created_at_to_app_option extends Migration
{
    public function safeUp()
    {
        $this->addColumn('app_option', 'created_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
    }

    public function safeDown()
    {
        $this->dropColumn('app_option', 'created_at');
    }
} 