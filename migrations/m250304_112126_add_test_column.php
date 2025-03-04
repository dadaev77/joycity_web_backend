<?php

use yii\db\Migration;

/**
 * Class m250304_112126_add_test_column
 */
class m250304_112126_add_test_column extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user', 'test', $this->float()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('user', 'test');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250304_112126_add_test_column cannot be reverted.\n";

        return false;
    }
    */
}
