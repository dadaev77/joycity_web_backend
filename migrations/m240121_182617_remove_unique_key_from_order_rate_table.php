<?php

use yii\db\Migration;

/**
 * Class m240121_182617_remove_unique_key_from_order_rate_table
 */
class m240121_182617_remove_unique_key_from_order_rate_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropForeignKey('fk_order_rate_order_id', 'order_rate');

        $this->dropIndex('fk_order_rate_order_id', 'order_rate');

        $this->createIndex(
            'fk_order_rate_order_id',
            'order_rate',
            'order_id',
            false,
        );

        $this->addForeignKey(
            'fk_order_rate_order_id',
            'order_rate',
            'order_id',
            'order',
            'id',
        );
    }
}
