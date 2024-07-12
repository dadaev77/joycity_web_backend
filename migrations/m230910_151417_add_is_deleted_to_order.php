<?php

use yii\db\Migration;

/**
 * Class m230910_151417_add_is_deleted_to_order
 */
class m230910_151417_add_is_deleted_to_order extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'order',
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
        echo "m230910_151417_add_is_deleted_to_order cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230910_151417_add_is_deleted_to_order cannot be reverted.\n";

        return false;
    }
    */
}
