<?php

use yii\db\Migration;

class m240219_000003_add_updated_at_to_buyer_offer extends Migration
{
    public function safeUp()
    {
        $this->addColumn('buyer_offer', 'updated_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
    }

    public function safeDown()
    {
        $this->dropColumn('buyer_offer', 'updated_at');
    }
} 