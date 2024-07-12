<?php

use yii\db\Migration;

/**
 * Class m230816_122715_change_price
 */
class m230816_122715_change_price extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            'product',
            'lot_size_price_level_one',
            $this->decimal(10, 2)->notNull()
        );
        $this->alterColumn(
            'product',
            'lot_size_price_level_two',
            $this->decimal(10, 2)
        );
        $this->alterColumn(
            'product',
            'lot_size_price_level_three',
            $this->decimal(10, 2)
        );
        $this->alterColumn(
            'product',
            'lot_size_price_level_four',
            $this->decimal(10, 2)
        );
        $this->alterColumn(
            'order_request',
            'price',
            $this->decimal(10, 2)->notNull()
        );
        $this->alterColumn('order', 'price', $this->decimal(10, 2)->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230816_122715_change_price cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230816_122715_change_price cannot be reverted.\n";

        return false;
    }
    */
}
