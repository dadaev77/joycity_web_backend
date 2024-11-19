<?php

use yii\db\Migration;

/**
 * Class m241008_073647_add_translation_fields_for_order_product
 */
class m241008_073647_add_translation_fields_for_order_product extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // product table
        $this->addColumn('product', 'name_eng', $this->string()->notNull());
        $this->addColumn('product', 'description_eng', $this->text()->notNull());
        $this->addColumn('product', 'name_zh', $this->string()->notNull());
        $this->addColumn('product', 'description_zh', $this->text()->notNull());

        // order table
        $this->addColumn('order', 'product_name_eng', $this->string()->notNull());
        $this->addColumn('order', 'product_description_eng', $this->text()->notNull());
        $this->addColumn('order', 'product_name_zh', $this->string()->notNull());
        $this->addColumn('order', 'product_description_zh', $this->text()->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241008_073647_add_translation_fields_for_order_product cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241008_073647_add_translation_fields_for_order_product cannot be reverted.\n";

        return false;
    }
    */
}
