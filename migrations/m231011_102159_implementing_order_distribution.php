<?php

use yii\db\Migration;

/**
 * Class m231011_102159_implementing_order_distribution
 */
class m231011_102159_implementing_order_distribution extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('order_distribution', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'current_buyer_id' => $this->integer()->notNull(),
            'requested_at' => $this->dateTime()->notNull(),
            'status' => $this->string()->notNull(),
            'buyer_ids_list' => $this->text()->notNull(),
        ]);

        $this->createIndex(
            'fk_order_distribution_order_id',
            'order_distribution',
            'order_id',
            true,
        );

        $this->addForeignKey(
            'fk_order_distribution_order_id',
            'order_distribution',
            'order_id',
            'order',
            'id',
        );
        $this->addForeignKey(
            'fk_order_distribution_current_buyer_id',
            'order_distribution',
            'current_buyer_id',
            'user',
            'id',
        );

        $this->addColumn(
            'user_settings',
            'use_only_selected_categories',
            $this->boolean()
                ->notNull()
                ->defaultValue(0),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m231011_102159_implementing_order_distribution cannot be reverted.\n";

        return false;
    }
}
