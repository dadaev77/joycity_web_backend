<?php

use yii\db\Migration;

/**
 * Class m230815_173127_change_product_name_table
 */
class m230815_173127_change_product_name_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->renameColumn(
            'product',
            'lot_size_level_one',
            'lot_size_level_one_min'
        );
        $this->renameColumn(
            'product',
            'lot_size_level_two',
            'lot_size_level_two_min'
        );
        $this->renameColumn(
            'product',
            'lot_size_level_three',
            'lot_size_level_three_min'
        );
        $this->renameColumn(
            'product',
            'lot_size_level_four',
            'lot_size_level_four_min'
        );

        $this->addColumn(
            'product',
            'lot_size_level_one_max',
            $this->integer()->notNull()
        );
        $this->addColumn('product', 'lot_size_level_two_max', $this->integer());
        $this->addColumn(
            'product',
            'lot_size_level_three_max',
            $this->integer()
        );
        $this->addColumn(
            'product',
            'lot_size_level_four_max',
            $this->integer()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230815_173127_change_product_name_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230815_173127_change_product_name_table cannot be reverted.\n";

        return false;
    }
    */
}
