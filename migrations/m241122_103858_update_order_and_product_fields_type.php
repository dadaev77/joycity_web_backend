<?php

use yii\db\Migration;

/**
 * Class m241122_103858_update_order_and_product_fields_type
 */
class m241122_103858_update_order_and_product_fields_type extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Order
        $this->alterColumn('order', 'expected_price_per_item', $this->float()->null());
        $this->alterColumn('order', 'price_product', $this->float()->defaultValue(0.00));
        $this->alterColumn('order', 'price_inspection', $this->float()->defaultValue(0.00));
        $this->alterColumn('order', 'price_packaging', $this->float()->defaultValue(0.00));
        $this->alterColumn('order', 'price_fulfilment', $this->float()->defaultValue(0.00));
        $this->alterColumn('order', 'price_delivery', $this->float()->defaultValue(0.00));

        // Product
        $this->alterColumn('product', 'range_1_price', $this->float()->null());
        $this->alterColumn('product', 'range_2_price', $this->float()->null());
        $this->alterColumn('product', 'range_3_price', $this->float()->null());
        $this->alterColumn('product', 'range_4_price', $this->float()->null());
        $this->alterColumn('product', 'product_height', $this->float()->defaultValue(0.0000));
        $this->alterColumn('product', 'product_width', $this->float()->defaultValue(0.0000));
        $this->alterColumn('product', 'product_depth', $this->float()->defaultValue(0.0000));
        $this->alterColumn('product', 'product_weight', $this->float()->defaultValue(0.0000));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241122_103858_update_order_and_product_fields_type cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241122_103858_update_order_and_product_fields_type cannot be reverted.\n";

        return false;
    }
    */
}
