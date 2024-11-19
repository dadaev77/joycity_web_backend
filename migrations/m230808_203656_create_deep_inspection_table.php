<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%deep_inspection}}`.
 */
class m230808_203656_create_deep_inspection_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('deep_inspection', [
            'id' => $this->primaryKey(),
            'price' => $this->decimal()->notNull(),
            'user_id' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'fk_deep_inspection_user1_idx',
            'deep_inspection',
            'user_id'
        );
        $this->addForeignKey(
            'fk_deep_inspection_user1',
            'deep_inspection',
            'user_id',
            'user',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }
}
