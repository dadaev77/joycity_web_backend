<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%messages}}`.
 */
class m250130_115528_create_messages_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%messages}}', [
            'id' => $this->bigPrimaryKey(),
            'chat_id' => $this->bigInteger(),
            'user_id' => $this->integer(),
            'type' => "ENUM('text', 'image', 'video', 'audio', 'file')",
            'content' => $this->json(),
            'metadata' => $this->json(),
            'reply_to_id' => $this->bigInteger(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'edited_at' => $this->timestamp()->null(),
            'deleted_at' => $this->timestamp()->null(),
            'status' => "ENUM('delivered', 'read')",
            'attachments' => $this->json(),
        ]);

        $this->createIndex(
            'idx-messages-chat_id-created_at',
            '{{%messages}}',
            ['chat_id', 'created_at']
        );

        $this->createIndex(
            'idx-messages-user_id',
            '{{%messages}}',
            'user_id'
        );

        $this->createIndex(
            'idx-messages-reply_to_id',
            '{{%messages}}',
            'reply_to_id'
        );

        $this->addForeignKey(
            'fk-messages-chat_id',
            '{{%messages}}',
            'chat_id',
            '{{%chats}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-messages-user_id',
            '{{%messages}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        if ($this->getDb()->schema->getTableSchema('{{%messages}}', true) !== null) {
            $this->dropTable('{{%messages}}', false);
        }
    }
}
