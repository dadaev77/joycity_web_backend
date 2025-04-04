<?php

use yii\db\Migration;

/**
 * Class m250404_104911_unset_role_prop_on_user
 */
class m250404_104911_unset_role_prop_on_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('user', 'role', $this->string()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250404_104911_unset_role_prop_on_user cannot be reverted.\n";
        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250404_104911_unset_role_prop_on_user cannot be reverted.\n";

        return false;
    }
    */
}
