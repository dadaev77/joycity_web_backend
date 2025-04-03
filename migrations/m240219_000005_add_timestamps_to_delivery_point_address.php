<?php

use yii\db\Migration;

class m240219_000005_add_timestamps_to_delivery_point_address extends Migration
{
    public function safeUp()
    {
        $this->addColumn('delivery_point_address', 'created_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
        $this->addColumn('delivery_point_address', 'updated_at', $this->timestamp()->defaultValue(new \yii\db\Expression('NOW()')));
    }

    public function safeDown()
    {
        $this->dropColumn('delivery_point_address', 'created_at');
        $this->dropColumn('delivery_point_address', 'updated_at');
    }
} 