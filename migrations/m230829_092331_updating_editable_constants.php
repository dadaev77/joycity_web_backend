<?php

use yii\db\Migration;

/**
 * Class m230829_092331_updating_editable_constants
 */
class m230829_092331_updating_editable_constants extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('type_delivery_point', [
            'id' => $this->primaryKey(),
            'en_name' => $this->string()->notNull(),
            'ru_name' => $this->string()->notNull(),
            'zh_name' => $this->string()->notNull(),
        ]);

        $this->createTable('delivery_point_address', [
            'id' => $this->primaryKey(),
            'type_delivery_point_id' => $this->integer()->notNull(),
            'address' => $this->string()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_delivery_point_address_type_delivery_point_id',
            'delivery_point_address',
            'type_delivery_point_id',
            'type_delivery_point',
            'id'
        );

        $this->renameTable('type_delivery_has_user', 'user_link_type_delivery');
        $this->renameTable(
            'type_packaging_has_user',
            'user_link_type_packaging'
        );
        $this->renameTable('user_has_category', 'user_link_category');

        $this->createTable('rate', [
            'id' => $this->primaryKey(),
            'RUB' => $this->float()->notNull(),
            'CNY' => $this->float()->notNull(),
        ]);

        $this->createTable('order_rate', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'RUB' => $this->float()->notNull(),
            'CNY' => $this->float()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230829_092331_updating_editable_constants cannot be reverted.\n";

        return false;
    }
}
