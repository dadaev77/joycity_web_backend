<?php

use yii\db\Migration;

class m240328_000003_insert_push_notification_types extends Migration
{
    public function safeUp()
    {
        // Очищаем таблицу перед вставкой
        $this->delete('{{%push_notification_types}}');

        $time = new \yii\db\Expression('NOW()');

        // Добавляем базовые типы уведомлений
        $this->batchInsert('{{%push_notification_types}}', 
            ['code', 'name', 'template', 'created_at', 'updated_at'],
            [
                ['new_chat_message', 'Новое сообщение в чате', 'Новое сообщение от {sender}', $time, $time],
                ['new_request', 'Новая заявка', 'Поступила новая заявка #{request_id}', $time, $time],
                ['status_changed', 'Изменение статуса', 'Статус заявки #{request_id} изменен на {status}', $time, $time],
            ]
        );
    }

    public function safeDown()
    {
        $this->delete('{{%push_notification_types}}');
    }
} 