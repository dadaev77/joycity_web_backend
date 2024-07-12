<?php

use yii\db\Migration;

/**
 * Class m230919_122946_implementing_order_manager_id
 */
class m230919_122946_implementing_order_manager_id extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'order',
            'manager_id',
            $this->integer()->after('buyer_id')
        );

        $this->addForeignKey(
            'fk_order_manager_id',
            'order',
            'manager_id',
            'user',
            'id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230919_122946_implementing_order_manager_id cannot be reverted.\n";

        return false;
    }
}
