<?php

use yii\db\Migration;

class m240328_000004_insert_test_push_services extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%push_services}}', [
            'user_id' => 1, // ID существующего пользователя
            'client_id' => 'test_client',
            'device_id' => 'test_device_123',
            'push_token' => 'fcm_test_token_123',
            'platform' => 'android',
            'last_active_at' => new \yii\db\Expression('NOW()'),
            'created_at' => new \yii\db\Expression('NOW()'),
            'updated_at' => new \yii\db\Expression('NOW()'),
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%push_services}}', ['device_id' => 'test_device_123']);
    }
} 