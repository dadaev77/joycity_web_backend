<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%type_delivery}}`.
 */
class m230808_185156_create_type_delivery_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('type_delivery', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'user_id' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'fk_type_delivery_user1_idx',
            'type_delivery',
            'user_id'
        );
        $this->addForeignKey(
            'fk_type_delivery_user1',
            'type_delivery',
            'user_id',
            'user',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }
}
