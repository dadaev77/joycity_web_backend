<?php

use yii\db\Migration;

class m240328_000002_create_push_notification_types_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%push_notification_types}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(50)->notNull()->unique(),
            'name' => $this->string(255)->notNull(),
            'template' => $this->text()->notNull(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Добавляем базовые типы уведомлений
        $this->batchInsert('{{%push_notification_types}}', 
            ['code', 'name', 'template'],
            [
                ['new_chat_message', 'Новое сообщение в чате', 'Новое сообщение от {sender}'],
                ['new_request', 'Новая заявка', 'Поступила новая заявка #{request_id}'],
                ['status_changed', 'Изменение статуса', 'Статус заявки #{request_id} изменен на {status}'],
            ]
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%push_notification_types}}');
    }
} 