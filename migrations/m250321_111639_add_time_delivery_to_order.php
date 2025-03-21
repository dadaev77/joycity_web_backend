<?php

use yii\db\Migration;

/**
 * Class m250321_111639_add_time_delivery_to_order
 */
class m250321_111639_add_time_delivery_to_order extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%order}}', 'timeDelivery', $this->integer()->defaultValue(0)->comment('Оставшееся время доставки в днях'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250321_111639_add_time_delivery_to_order cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250321_111639_add_time_delivery_to_order cannot be reverted.\n";

        return false;
    }
    */
}
