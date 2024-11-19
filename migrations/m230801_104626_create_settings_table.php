<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%settings}}`.
 */
class m230801_104626_create_settings_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('user_settings', [
            'id' => $this->primaryKey(),
            'enable_notifications' => $this->integer()
                ->notNull()
                ->defaultValue(1),
            'currency' => $this->string(40)
                ->notNull()
                ->defaultValue('rub'),
            'application_language' => $this->string(40)
                ->notNull()
                ->defaultValue('ru'),
            'chat_language' => $this->string(40)
                ->notNull()
                ->defaultValue('ru'),
            'user_id' => $this->integer()->notNull(),
        ]);

        $this->createIndex('fk_settings_user1_idx', 'user_settings', 'user_id');
        $this->addForeignKey(
            'fk_settings_user1',
            'user_settings',
            'user_id',
            'user',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }
}
