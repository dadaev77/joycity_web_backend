<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%type_packaging}}`.
 */
class m230808_170904_create_type_packaging_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $this->createTable('type_packaging', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'user_id' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'fk_type_packaging_user1_idx',
            'type_packaging',
            'user_id'
        );
        $this->addForeignKey(
            'fk_type_packaging_user1',
            'type_packaging',
            'user_id',
            'user',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }
}
