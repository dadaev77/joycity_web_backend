<?php

use yii\db\Migration;

/**
 * Class m231205_145812_price_decimal_convertation
 */
class m231205_145812_price_decimal_convertation extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // buyer offer
        $this->alterColumn(
            'buyer_offer',
            'price_product',
            $this->decimal(10, 4)->notNull(),
        );
        $this->alterColumn(
            'buyer_offer',
            'price_inspection',
            $this->decimal(10, 4)->notNull(),
        );
        $this->alterColumn(
            'buyer_offer',
            'price_packaging',
            $this->decimal(10, 4)->notNull(),
        );

        // order
        $this->alterColumn(
            'order',
            'expected_price_per_item',
            $this->decimal(10, 4)->notNull(),
        );
        $this->alterColumn(
            'order',
            'price_product',
            $this->decimal(10, 4)
                ->defaultValue(0)
                ->notNull(),
        );
        $this->alterColumn(
            'order',
            'price_inspection',
            $this->decimal(10, 4)
                ->defaultValue(0)
                ->notNull(),
        );
        $this->alterColumn(
            'order',
            'price_packaging',
            $this->decimal(10, 4)
                ->defaultValue(0)
                ->notNull(),
        );
        $this->alterColumn(
            'order',
            'price_fulfilment',
            $this->decimal(10, 4)
                ->defaultValue(0)
                ->notNull(),
        );
        $this->alterColumn(
            'order',
            'price_delivery',
            $this->decimal(10, 4)
                ->defaultValue(0)
                ->notNull(),
        );

        // product
        $this->alterColumn(
            'product',
            'rating',
            $this->decimal(10, 2)
                ->defaultValue(0)
                ->notNull(),
        );
        $this->alterColumn(
            'product',
            'range_1_price',
            $this->decimal(10, 4)->notNull(),
        );
        $this->alterColumn('product', 'range_2_price', $this->decimal(10, 4));
        $this->alterColumn('product', 'range_3_price', $this->decimal(10, 4));
        $this->alterColumn('product', 'range_4_price', $this->decimal(10, 4));

        // user_verification_request
        $this->alterColumn(
            'user_verification_request',
            'amount',
            $this->decimal(10, 4)
                ->defaultValue(0)
                ->notNull(),
        );

        // rate
        $this->alterColumn('rate', 'RUB', $this->decimal(10, 4)->notNull());
        $this->alterColumn('rate', 'CNY', $this->decimal(10, 4)->notNull());

        // order_rate
        $this->alterColumn(
            'order_rate',
            'RUB',
            $this->decimal(10, 4)->notNull(),
        );
        $this->alterColumn(
            'order_rate',
            'CNY',
            $this->decimal(10, 4)->notNull(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // buyer_offer
        $this->alterColumn(
            'buyer_offer',
            'price_product',
            $this->float()->notNull(),
        );
        $this->alterColumn(
            'buyer_offer',
            'price_inspection',
            $this->float()->notNull(),
        );
        $this->alterColumn(
            'buyer_offer',
            'price_packaging',
            $this->float()->notNull(),
        );

        // order
        $this->alterColumn(
            'order',
            'expected_price_per_item',
            $this->float()->notNull(),
        );
        $this->alterColumn(
            'order',
            'price_product',
            $this->float()
                ->defaultValue(0)
                ->notNull(),
        );
        $this->alterColumn(
            'order',
            'price_inspection',
            $this->float()
                ->defaultValue(0)
                ->notNull(),
        );
        $this->alterColumn(
            'order',
            'price_packaging',
            $this->float()
                ->defaultValue(0)
                ->notNull(),
        );
        $this->alterColumn(
            'order',
            'price_fulfilment',
            $this->float()
                ->defaultValue(0)
                ->notNull(),
        );
        $this->alterColumn(
            'order',
            'price_delivery',
            $this->float()
                ->defaultValue(0)
                ->notNull(),
        );

        // product
        $this->alterColumn(
            'order',
            'expected_price_per_item',
            $this->float()->notNull(),
        );
        $this->alterColumn(
            'order',
            'price_product',
            $this->float()
                ->defaultValue(0)
                ->notNull(),
        );
        $this->alterColumn(
            'order',
            'price_inspection',
            $this->float()
                ->defaultValue(0)
                ->notNull(),
        );
        $this->alterColumn(
            'order',
            'price_packaging',
            $this->float()
                ->defaultValue(0)
                ->notNull(),
        );
        $this->alterColumn(
            'order',
            'price_fulfilment',
            $this->float()
                ->defaultValue(0)
                ->notNull(),
        );
        $this->alterColumn(
            'order',
            'price_delivery',
            $this->float()
                ->defaultValue(0)
                ->notNull(),
        );

        // user_verification_request
        $this->alterColumn(
            'user_verification_request',
            'amount',
            $this->float()
                ->defaultValue(0)
                ->notNull(),
        );

        // rate
        $this->alterColumn('rate', 'RUB', $this->float()->notNull());
        $this->alterColumn('rate', 'CNY', $this->float()->notNull());

        // order_rate
        $this->alterColumn('order_rate', 'RUB', $this->float()->notNull());
        $this->alterColumn('order_rate', 'CNY', $this->float()->notNull());
    }
}
