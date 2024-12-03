<?php

use yii\db\Migration;

/**
 * Создание таблицы накладных
 */
class m240101_000000_create_waybill_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%waybill}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'file_path' => $this->string()->notNull(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'regenerated_at' => $this->timestamp()->null(),
        ]);

        // Создаем индекс для внешнего ключа order_id
        $this->createIndex(
            'idx-waybill-order_id',
            '{{%waybill}}',
            'order_id'
        );

        // Добавляем внешний ключ на таблицу заказов
        $this->addForeignKey(
            'fk-waybill-order_id',
            '{{%waybill}}',
            'order_id',
            '{{%order}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-waybill-order_id', '{{%waybill}}');
        $this->dropIndex('idx-waybill-order_id', '{{%waybill}}');
        $this->dropTable('{{%waybill}}');
    }
}
