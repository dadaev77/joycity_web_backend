<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%order_tracking}}`.
 */
class m230914_154307_create_order_tracking_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('order_tracking', [
            'id' => $this->primaryKey(),
            'created_at' => $this->dateTime()->notNull(),
            'type' => $this->string(255)->notNull(),
            'order_id' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'idx-order_tracking-order_id',
            'order_tracking',
            'order_id'
        );

        $this->addForeignKey(
            'fk-order_tracking-order_id',
            'order_tracking',
            'order_id',
            'order',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%order_tracking}}');
    }
}
