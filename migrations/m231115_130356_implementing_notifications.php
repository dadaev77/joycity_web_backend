<?php

use yii\db\Migration;

/**
 * Class m231115_130356_implementing_notifications
 */
class m231115_130356_implementing_notifications extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('notification', [
            'id' => $this->primaryKey(),
            'created_at' => $this->dateTime()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'is_read' => $this->boolean()
                ->notNull()
                ->defaultValue(0),
            'event' => $this->string()->notNull(),
            'entity_type' => $this->string()->notNull(),
            'entity_id' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_notification_user_id',
            'notification',
            'user_id',
            'user',
            'id',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('notification');
    }
}
