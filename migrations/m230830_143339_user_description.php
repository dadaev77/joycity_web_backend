<?php

use yii\db\Migration;

/**
 * Class m230830_143339_user_description
 */
class m230830_143339_user_description extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user', 'description', $this->text()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230830_143339_user_description cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230830_143339_user_description cannot be reverted.\n";

        return false;
    }
    */
}
