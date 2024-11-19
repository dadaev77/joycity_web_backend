<?php

use yii\db\Migration;

/**
 * Class m240127_004820_alter_delivery_point_address_user_id
 */
class m240127_004820_alter_delivery_point_address_user_id extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            'delivery_point_address',
            'user_id',
            $this->integer(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn(
            'delivery_point_address',
            'user_id',
            $this->integer()->notNull(),
        );
    }
}
