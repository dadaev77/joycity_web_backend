<?php

use yii\db\Migration;

/**
 * Class m231120_000001_add_fields_to_buyer_delivery_offer
 */
class m231120_000001_add_fields_to_buyer_delivery_offer extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('buyer_delivery_offer', 'package_expenses', $this->decimal(10, 2)->notNull()->defaultValue(0));
        $this->addColumn('buyer_delivery_offer', 'amount_of_space', $this->integer()->notNull()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('buyer_delivery_offer', 'package_expenses');
        $this->dropColumn('buyer_delivery_offer', 'amount_of_space');
    }
}
