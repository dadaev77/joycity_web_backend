<?php

use yii\db\Migration;

/**
 * Class m241127_103843_add_currency_to_buyer_delivery_offer
 */
class m241127_103843_add_currency_to_buyer_delivery_offer extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('buyer_delivery_offer', 'currency', $this->string(3)->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241127_103843_add_currency_to_buyer_delivery_offer cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241127_103843_add_currency_to_buyer_delivery_offer cannot be reverted.\n";

        return false;
    }
    */
}
