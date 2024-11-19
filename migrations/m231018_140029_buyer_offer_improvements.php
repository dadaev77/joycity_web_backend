<?php

use yii\db\Migration;

/**
 * Class m231018_140029_buyer_offer_improvements
 */
class m231018_140029_buyer_offer_improvements extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'buyer_offer',
            'total_quantity',
            $this->integer()->notNull(),
        );

        $this->dropColumn('buyer_offer', 'price_delivery');
        $this->dropColumn('buyer_offer', 'price_fulfilment');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m231018_140029_buyer_offer_improvements cannot be reverted.\n";

        return false;
    }
}
