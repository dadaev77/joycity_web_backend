<?php

use yii\db\Migration;

/**
 * Class m231222_120445_add_fulfillment_id_to_order
 */
class m231222_120445_add_fulfillment_id_to_order extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'order',
            'fulfillment_id',
            $this->integer()
                ->null()
                ->after('id'),
        );

        $this->createIndex('fk_order_user3_idx', 'order', 'fulfillment_id');
        $this->addForeignKey(
            'fk_order_user3',
            'order',
            'fulfillment_id',
            'user',
            'id',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_order_user3', 'order');
        $this->dropIndex('fk_order_user3_idx', 'order');

        $this->dropColumn('order', 'fulfillment_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m231222_120445_add_fulfillment_id_to_order cannot be reverted.\n";

        return false;
    }
    */
}
