<?php

use yii\db\Migration;

/**
 * Class m241018_103814_update_category_table
 */
class m241018_103814_update_category_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('category', 'parent_id', $this->integer()->null()->defaultValue(null));
        $this->addForeignKey(
            'fk-category-parent_id',
            'category',
            'parent_id',
            'category',
            'id',
            'CASCADE',
            'SET NULL'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241018_103814_update_category_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241018_103814_update_category_table cannot be reverted.\n";

        return false;
    }
    */
}
