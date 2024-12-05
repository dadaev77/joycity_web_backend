<?php

use yii\db\Migration;

/**
 * Class m241205_160003_update_waybill
 */
class m241205_160003_update_waybill extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('waybill', 'date_of_production', $this->string());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241205_160003_update_waybill cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241205_160003_update_waybill cannot be reverted.\n";

        return false;
    }
    */
}
