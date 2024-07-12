<?php

use yii\db\Migration;

/**
 * Class m231004_105617_implementing_one_to_one_link
 */
class m231004_105617_implementing_one_to_one_link extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropForeignKey('fk_settings_user1', 'user_settings');
        $this->dropIndex('fk_settings_user1_idx', 'user_settings');

        $this->createIndex(
            'fk_user_settings_user_id',
            'user_settings',
            'user_id',
            true,
        );
        $this->addForeignKey(
            'fk_user_settings_user_id',
            'user_settings',
            'user_id',
            'user',
            'id',
        );

        $this->dropForeignKey('fk_order_rate', 'order_rate');
        $this->dropIndex('fk_order_rate', 'order_rate');

        $this->createIndex(
            'fk_order_rate_order_id',
            'order_rate',
            'order_id',
            true,
        );
        $this->addForeignKey(
            'fk_order_rate_order_id',
            'order_rate',
            'order_id',
            'order',
            'id',
        );

        $this->alterColumn(
            'user_settings',
            'enable_notifications',
            $this->boolean()
                ->notNull()
                ->defaultValue(1),
        );
        $this->alterColumn(
            'user_settings',
            'currency',
            $this->string(10)->notNull(),
        );
        $this->alterColumn(
            'user_settings',
            'application_language',
            $this->string(10)->notNull(),
        );
        $this->alterColumn(
            'user_settings',
            'chat_language',
            $this->string(10)->notNull(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m231004_105617_implementing_one_to_one_link cannot be reverted.\n";

        return false;
    }
}
