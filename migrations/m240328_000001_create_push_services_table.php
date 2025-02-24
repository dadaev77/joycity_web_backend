<?php

use yii\db\Migration;

class m240328_000001_create_push_services_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%push_services}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'client_id' => $this->string(255)->notNull(),
            'device_id' => $this->string(255)->notNull(),
            'push_token' => $this->string(255),
            'platform' => $this->string(20), // ios/android
            'last_active_at' => $this->dateTime(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex(
            'idx-push_services-user_id',
            '{{%push_services}}',
            'user_id'
        );

        $this->createIndex(
            'idx-push_services-device_id',
            '{{%push_services}}',
            'device_id'
        );

        $this->addForeignKey(
            'fk-push_services-user_id',
            '{{%push_services}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%push_services}}');
    }
} 