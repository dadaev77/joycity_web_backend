<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%verification_request}}`.
 */
class m230917_201855_create_verification_request_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('user_verification_request', [
            'id' => $this->primaryKey(),
            'created_by_id' => $this->integer()->notNull(),
            'approved_by_id' => $this->integer(),
            'created_at' => $this->dateTime()->notNull(),
            'amount' => $this->float()
                ->notNull()
                ->defaultValue(0),
            'status' => $this->boolean()
                ->notNull()
                ->defaultValue(0),
        ]);

        $this->addForeignKey(
            'fk_user_verification_request_created_by_id',
            'user_verification_request',
            'created_by_id',
            'user',
            'id'
        );
        $this->addForeignKey(
            'fk_user_verification_request_approved_by_id',
            'user_verification_request',
            'approved_by_id',
            'user',
            'id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%verification_request}}');
    }
}
