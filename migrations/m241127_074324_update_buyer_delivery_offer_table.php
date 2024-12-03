<?php

use yii\db\Migration;

/**
 * Class m241127_074324_update_buyer_delivery_offer_table
 */
class m241127_074324_update_buyer_delivery_offer_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('buyer_delivery_offer', 'price_product', $this->float()->notNull());
        $this->alterColumn('buyer_delivery_offer', 'product_height', $this->float()->notNull()->defaultValue(0));
        $this->alterColumn('buyer_delivery_offer', 'product_width', $this->float()->notNull()->defaultValue(0));
        $this->alterColumn('buyer_delivery_offer', 'product_depth', $this->float()->notNull()->defaultValue(0));
        $this->alterColumn('buyer_delivery_offer', 'product_weight', $this->float()->notNull()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241127_074324_update_buyer_delivery_offer_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241127_074324_update_buyer_delivery_offer_table cannot be reverted.\n";

        return false;
    }
    */
}
