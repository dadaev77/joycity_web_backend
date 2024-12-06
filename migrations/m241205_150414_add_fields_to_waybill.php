<?php

use yii\db\Migration;

/**
 * Class m241205_150414_add_fields_to_waybill
 */
class m241205_150414_add_fields_to_waybill extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('waybill', 'price_per_kg', $this->decimal(10, 2)->defaultValue(0));
        $this->addColumn('waybill', 'course', $this->decimal(10, 2)->defaultValue(0));
        $this->addColumn('waybill', 'total_number_pairs', $this->integer()->defaultValue(0));
        $this->addColumn('waybill', 'total_customs_duty', $this->decimal(10, 2)->defaultValue(0));
        $this->addColumn('waybill', 'volume_costs', $this->decimal(10, 2)->defaultValue(0));
        $this->addColumn('waybill', 'date_of_production', $this->date());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241205_150414_add_fields_to_waybill cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241205_150414_add_fields_to_waybill cannot be reverted.\n";

        return false;
    }
    */
}
