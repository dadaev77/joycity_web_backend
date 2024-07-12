<?php

use yii\db\Migration;

/**
 * Class m230529_191447_init_user_request_enabled
 */
class m230529_191447_init_user_request_enabled extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $this->createTable('user_request_enabled', [
            'id' => $this->primaryKey(),
            'created_at' => $this->dateTime()->notNull(),
            'request_enabled' => $this->string(255)->notNull(),
            'author_id' => $this->integer()->notNull(),
            'manager_id' => $this->integer()->NULL(),
            'author_name' => $this->string(255)->notNull(),
        ]);

        $this->createIndex(
            'fk_user_request_enabled_user1_idx',
            'user_request_enabled',
            'author_id'
        );
        $this->createIndex(
            'fk_user_request_enabled_user2_idx',
            'user_request_enabled',
            'manager_id'
        );

        $this->addForeignKey(
            'fk_user_request_enabled_user1',
            'user_request_enabled',
            'author_id',
            'user',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            'fk_user_request_enabled_user2',
            'user_request_enabled',
            'manager_id',
            'user',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->dropForeignKey(
            'fk_user_request_enabled_user1',
            'user_request_enabled'
        );
        $this->dropForeignKey(
            'fk_user_request_enabled_user2',
            'user_request_enabled'
        );

        $this->dropIndex(
            'fk_user_request_enabled_user1_idx',
            'user_request_enabled'
        );
        $this->dropIndex(
            'fk_user_request_enabled_user2_idx',
            'user_request_enabled'
        );

        $this->dropTable('user_request_enabled');
    }
    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230529_191447_init_user_request_enabled cannot be reverted.\n";

        return false;
    }
    */
}
