<?php

use yii\db\Migration;

/**
 * Class m230904_105926_linking_order_rate
 */
class m230904_105926_linking_order_rate extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addForeignKey(
            'fk_order_rate',
            'order_rate',
            'order_id',
            'order',
            'id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230904_105926_linking_order_rate cannot be reverted.\n";

        return false;
    }
}
