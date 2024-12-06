<?php

use yii\db\Migration;

/**
 * Class m231121_000001_add_default_value_to_total_packaging_quantity
 */
class m231121_000001_add_default_value_to_total_packaging_quantity extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('buyer_delivery_offer', 'total_packaging_quantity', $this->integer()->defaultValue(1)->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('buyer_delivery_offer', 'total_packaging_quantity', $this->integer());
    }
}
