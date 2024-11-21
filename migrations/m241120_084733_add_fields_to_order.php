<?php

use yii\db\Migration;

/**
 * Class m241120_084733_add_fields_to_order
 */
class m241120_084733_add_fields_to_order extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('order', 'amount_of_space', $this->integer()->notNull());
        $this->addColumn('order', 'cargo_number', $this->string()->notNull());
        echo "added fields: amount_of_space, cargo_number.\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "drop fields: amount_of_space, cargo_number.\n";
        $this->dropColumn('order', 'amount_of_space');
        $this->dropColumn('order', 'cargo_number');
        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241120_084733_add_fields_to_order cannot be reverted.\n";

        return false;
    }
    */
}
