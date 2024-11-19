<?php

use yii\db\Migration;

/**
 * Class m241008_121523_change_eng_to_en_prod_order
 */
class m241008_121523_change_eng_to_en_prod_order extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->renameColumn('product', 'name_eng', 'name_en');
        $this->renameColumn('product', 'description_eng', 'description_en');
        $this->renameColumn('order', 'product_name_eng', 'product_name_en');
        $this->renameColumn('order', 'product_description_eng', 'product_description_en');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241008_121523_change_eng_to_en_prod_order cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241008_121523_change_eng_to_en_prod_order cannot be reverted.\n";

        return false;
    }
    */
}
