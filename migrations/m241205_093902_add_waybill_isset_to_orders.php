<?php

use yii\db\Migration;

/**
 * Class m241205_093902_add_waybill_isset_to_orders
 */
class m241205_093902_add_waybill_isset_to_orders extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%order}}', 'waybill_isset', $this->boolean()->defaultValue(false));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%order}}', 'waybill_isset');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241205_093902_add_waybill_isset_to_orders cannot be reverted.\n";

        return false;
    }
    */
}
