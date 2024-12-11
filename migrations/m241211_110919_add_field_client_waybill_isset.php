<?php

use yii\db\Migration;

/**
 * Class m241211_110919_add_field_client_waybill_isset
 */
class m241211_110919_add_field_client_waybill_isset extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('order', 'client_waybill_isset', $this->boolean()->defaultValue(false));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241211_110919_add_field_client_waybill_isset cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241211_110919_add_field_client_waybill_isset cannot be reverted.\n";

        return false;
    }
    */
}
