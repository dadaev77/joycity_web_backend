<?php

use yii\db\Migration;

/**
 * Class m230828_172044_add_avatar_id_to_user_table
 */
class m230828_172044_add_avatar_id_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user', 'avatar_id', $this->integer()->null());

        $this->addForeignKey(
            'fk_user_avatar_id',
            'user',
            'avatar_id',
            'attachment',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230828_172044_add_avatar_id_to_user_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230828_172044_add_avatar_id_to_user_table cannot be reverted.\n";

        return false;
    }
    */
}
