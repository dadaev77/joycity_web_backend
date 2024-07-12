<?php

use yii\db\Migration;

/**
 * Class m231030_201545_add_is_deleted_to_delivery_point_address
 */
class m231030_201545_add_is_deleted_to_delivery_point_address extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'delivery_point_address',
            'is_deleted',
            $this->tinyInteger(1)
                ->notNull()
                ->defaultValue(0),
        );
    }

    /**
     * {@inheritdoc}
     */
    //    public function safeDown()
    //    {
    //        echo "m231030_201545_add_is_deleted_to_delivery_point_address cannot be reverted.\n";
    //
    //        return false;
    //    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m231030_201545_add_is_deleted_to_delivery_point_address cannot be reverted.\n";

        return false;
    }
    */
}
