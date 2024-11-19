<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%type_destination}}`.
 */
class m230808_172721_create_type_destination_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('type_destination', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'user_id' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_type_destination_user1',
            'type_destination',
            'user_id',
            'user',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }
}
