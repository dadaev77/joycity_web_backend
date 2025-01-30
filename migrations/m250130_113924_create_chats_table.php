<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%chats}}`.
 */
class m250130_113924_create_chats_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%chats}}', [
            'id' => $this->bigPrimaryKey(),
            'type' => "ENUM('private', 'group')",
            'name' => $this->string(),
            'created_at' => $this->timestamp(),
            'updated_at' => $this->timestamp(),
            'last_message_id' => $this->bigInteger(),
            'status' => "ENUM('active', 'archived')",
            'order_id' => $this->bigInteger(),
            'verification_id' => $this->bigInteger(),
            'metadata' => $this->json(),
            'user_id' => $this->bigInteger(),
            'role' => "ENUM('owner', 'admin', 'member')",
            'left_at' => $this->timestamp(),
            'is_muted' => $this->boolean(),
            'joined_at' => $this->timestamp(),
            'last_read_message_id' => $this->bigInteger(),
        ]);

        // Add indexes
        $this->createIndex('idx-chats-last_message_id', '{{%chats}}', 'last_message_id');
        $this->createIndex('idx-chats-user_id', '{{%chats}}', 'user_id');
        $this->createIndex('idx-chats-order_id_verification_id', '{{%chats}}', ['order_id', 'verification_id']);
        $this->createIndex('idx-chats-created_at', '{{%chats}}', 'created_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        if ($this->getDb()->schema->getTableSchema('{{%chats}}', true) !== null) {
            $this->dropTable('{{%chats}}', false);
        }
    }
}
