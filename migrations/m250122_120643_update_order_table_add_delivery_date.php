<?php

use yii\db\Migration;

/**
 * Class m250122_120643_update_order_table_add_delivery_date
 */
class m250122_120643_update_order_table_add_delivery_date extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%order}}', 'delivery_start_date', $this->timestamp());
        $this->addColumn('{{%order}}', 'delivery_days_expected', $this->integer()->defaultValue(0));
        $this->addColumn('{{%order}}', 'delivery_delay_days', $this->integer()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250122_120643_update_order_table_add_delivery_date cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250122_120643_update_order_table_add_delivery_date cannot be reverted.\n";

        return false;
    }
    */
}
