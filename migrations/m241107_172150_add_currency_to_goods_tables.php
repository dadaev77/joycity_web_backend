<?php

use yii\db\Migration;

/**
 * Class m241107_172150_add_currency_to_goods_tables
 */
class m241107_172150_add_currency_to_goods_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('order', 'currency', $this->string(3)->defaultValue('RUB'));
        $this->addColumn('product', 'currency', $this->string(3)->defaultValue('RUB'));
        $this->addColumn('buyer_offer', 'currency', $this->string(3)->defaultValue('RUB'));
        $this->addColumn('fulfillment_offer', 'currency', $this->string(3)->defaultValue('RUB'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241107_172150_add_currency_to_goods_tables cannot be reverted.\n";

        $this->dropColumn('order', 'currency');
        $this->dropColumn('product', 'currency');
        $this->dropColumn('buyer_offer', 'currency');
        $this->dropColumn('fulfillment_offer', 'currency');

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241107_172150_add_currency_to_goods_tables cannot be reverted.\n";

        return false;
    }
    */
}
