<?php

use yii\db\Migration;

/**
 * Class m250403_141554_add_role_id_to_users_table
 */
class m250403_141554_add_role_id_to_users_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'role_id', $this->integer()->notNull()->defaultValue(1));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%users}}', 'role_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250403_141554_add_role_id_to_users_table cannot be reverted.\n";

        return false;
    }
    */
}
