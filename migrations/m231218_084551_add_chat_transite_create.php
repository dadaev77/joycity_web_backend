<?php

use yii\db\Migration;

/**
 * Class m231218_084551_add_chat_transite_create
 */
class m231218_084551_add_chat_transite_create extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('chat_translate', [
            'id' => $this->primaryKey(),
            'message_key' => $this->string()
                ->notNull()
                ->unique(),
            'ru' => $this->string()
                ->notNull()
                ->defaultValue(''),
            'zh' => $this->string()
                ->notNull()
                ->defaultValue(''),
            'en' => $this->string()
                ->notNull()
                ->defaultValue(''),
        ]);

        $this->createIndex(
            'idx-chat_translate-message_key',
            'chat_translate',
            'message_key',
        );
    }

    public function safeDown()
    {
        $this->dropIndex('idx-chat_translate-message_key', 'chat_translate');

        $this->dropTable('chat_translate');
    }
}
