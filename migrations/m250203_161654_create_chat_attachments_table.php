<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%chat_attachments}}`.
 */
class m250203_161654_create_chat_attachments_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%chat_attachments}}', [
            'id' => $this->primaryKey(),
            'message_id' => $this->bigInteger()->notNull(),

            'type' => "ENUM('image', 'video', 'audio', 'file')",

            'file_name' => $this->string()->null(),
            'file_path' => $this->string()->null(),
            'file_size' => $this->integer()->null(),
            'mime_type' => $this->string()->null(),

            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%chat_attachments}}');
    }
}
