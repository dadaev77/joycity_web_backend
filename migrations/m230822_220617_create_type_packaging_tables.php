<?php

use yii\db\Migration;

/**
 * Class m230822_220617_create_type_packaging_tables
 */
class m230822_220617_create_type_packaging_tables extends Migration
{

    public function up()
    {
        // Создаем таблицу type_packaging
        $this->createTable('{{%type_packaging}}', [
            'id' => $this->primaryKey(),
            'en_name' => $this->string(255)->notNull(),
            'ru_name' => $this->string(255)->notNull(),
            'zh_name' => $this->string(255)->notNull(),
        ]);

        // Создаем таблицу type_packaging_has_user
        $this->createTable('{{%type_packaging_has_user}}', [
            'id' => $this->primaryKey(),
            'type_packaging_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
        ]);

        // Создаем индексы и внешние ключи для связи таблицы type_packaging_has_user
        $this->createIndex('{{%fk_type_packaging_has_user_user1_idx}}', '{{%type_packaging_has_user}}', 'user_id');
        $this->createIndex('{{%fk_type_packaging_has_user_type_packaging1_idx}}', '{{%type_packaging_has_user}}', 'type_packaging_id');

        $this->addForeignKey('{{%fk_type_packaging_has_user_user1}}', '{{%type_packaging_has_user}}', 'user_id', '{{%user}}', 'id', 'NO ACTION', 'NO ACTION');
        $this->addForeignKey('{{%fk_type_packaging_has_user_type_packaging1}}', '{{%type_packaging_has_user}}', 'type_packaging_id', '{{%type_packaging}}', 'id', 'NO ACTION', 'NO ACTION');
    }

}
