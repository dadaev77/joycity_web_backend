<?php

use yii\db\Migration;

/**
 * Class m230822_221419_create_type_delivery_tables
 */
class m230822_221419_create_type_delivery_tables extends Migration
{
    public function up()
    {
        // Создаем таблицу type_delivery
        $this->createTable('{{%type_delivery}}', [
            'id' => $this->primaryKey(),
            'en_name' => $this->string(255)->notNull(),
            'ru_name' => $this->string(255)->notNull(),
            'zh_name' => $this->string(255)->notNull(),
        ]);

        // Создаем таблицу type_delivery_has_user
        $this->createTable('{{%type_delivery_has_user}}', [
            'id' => $this->primaryKey(),
            'type_delivery_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
        ]);

        // Создаем индексы и внешние ключи для связи таблицы type_delivery_has_user
        $this->createIndex('{{%fk_type_delivery_has_user_user1_idx}}', '{{%type_delivery_has_user}}', 'user_id');
        $this->createIndex('{{%fk_type_delivery_has_user_type_delivery1_idx}}', '{{%type_delivery_has_user}}', 'type_delivery_id');

        $this->addForeignKey('{{%fk_type_delivery_has_user_user1}}', '{{%type_delivery_has_user}}', 'user_id', '{{%user}}', 'id', 'NO ACTION', 'NO ACTION');
        $this->addForeignKey('{{%fk_type_delivery_has_user_type_delivery1}}', '{{%type_delivery_has_user}}', 'type_delivery_id', '{{%type_delivery}}', 'id', 'NO ACTION', 'NO ACTION');
    }

}
