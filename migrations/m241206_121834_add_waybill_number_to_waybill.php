<?php

use yii\db\Migration;

/**
 * Class m241206_121834_add_waybill_number_to_waybill
 */
class m241206_121834_add_waybill_number_to_waybill extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('waybill', 'waybill_number', $this->string(255)->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241206_121834_add_waybill_number_to_waybill cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241206_121834_add_waybill_number_to_waybill cannot be reverted.\n";

        return false;
    }
    */
}
