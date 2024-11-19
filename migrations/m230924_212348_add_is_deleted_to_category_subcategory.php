<?php

use yii\db\Migration;

/**
 * Class m230924_212348_add_is_deleted_to_category_subcategory
 */
class m230924_212348_add_is_deleted_to_category_subcategory extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'category',
            'is_deleted',
            $this->tinyInteger(1)
                ->notNull()
                ->defaultValue(0)
        );
        $this->addColumn(
            'subcategory',
            'is_deleted',
            $this->tinyInteger(1)
                ->notNull()
                ->defaultValue(0)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230924_212348_add_is_deleted_to_category_subcategory cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230924_212348_add_is_deleted_to_category_subcategory cannot be reverted.\n";

        return false;
    }
    */
}
