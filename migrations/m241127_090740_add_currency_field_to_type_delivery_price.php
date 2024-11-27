<?php

use yii\db\Migration;

/**
 * Class m241127_090740_add_currency_field_to_type_delivery_price
 */
class m241127_090740_add_currency_field_to_type_delivery_price extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('type_delivery_price', 'currency', $this->string(3)->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241127_090740_add_currency_field_to_type_delivery_price cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241127_090740_add_currency_field_to_type_delivery_price cannot be reverted.\n";

        return false;
    }
    */
}
