<?php

use yii\db\Migration;

/**
 * Class m250312_131250_add_declined_by_byers
 */
class m250312_131250_add_declined_by_byers extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('order_distribution', 'declined_by', $this->text()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250312_131250_add_declined_by_byers cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250312_131250_add_declined_by_byers cannot be reverted.\n";

        return false;
    }
    */
}
