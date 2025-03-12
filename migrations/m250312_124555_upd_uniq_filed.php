<?php

use yii\db\Migration;

/**
 * Class m250312_124555_upd_uniq_filed
 */
class m250312_124555_upd_uniq_filed extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Удаляем внешнее ограничение, использующее индекс
        $this->dropForeignKey('fk_order_distribution_order_id', 'order_distribution');
        $this->dropIndex('order_id', 'order_distribution');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Восстанавливаем уникальный индекс с поля order_id
        $this->createIndex('order_id', 'order_distribution', 'order_id', true);

        // Восстанавливаем внешнее ограничение
        $this->addForeignKey('fk_order_distribution_order_id', 'order_distribution', 'order_id', 'order', 'id', 'CASCADE', 'CASCADE');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250312_124555_upd_uniq_filed cannot be reverted.\n";

        return false;
    }
    */
}
