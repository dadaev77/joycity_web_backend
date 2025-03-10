<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%push_notification}}`.
 */
class m250225_095056_create_push_notification_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%push_notification}}', [
            'id' => $this->primaryKey(),
            'client_id' => $this->integer()->notNull(),
            'device_id' => $this->string(255)->notNull(),
            'push_token' => $this->string(255)->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%push_notification}}');
    }
}