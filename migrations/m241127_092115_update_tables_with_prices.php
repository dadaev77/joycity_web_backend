<?php

use yii\db\Migration;

/**
 * Class m241127_092115_update_tables_with_prices
 */
class m241127_092115_update_tables_with_prices extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('buyer_offer', 'price_product', $this->float()->notNull());
        $this->alterColumn('buyer_offer', 'price_inspection', $this->float()->notNull());

        $this->alterColumn('buyer_delivery_offer', 'product_height', $this->float()->notNull());
        $this->alterColumn('buyer_delivery_offer', 'product_width', $this->float()->notNull());
        $this->alterColumn('buyer_delivery_offer', 'product_depth', $this->float()->notNull());
        $this->alterColumn('buyer_delivery_offer', 'product_weight', $this->float()->notNull());

        $this->alterColumn('type_delivery_price', 'price', $this->float()->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241127_092115_update_tables_with_prices cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241127_092115_update_tables_with_prices cannot be reverted.\n";

        return false;
    }
    */
}
