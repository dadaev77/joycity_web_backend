<?php

use yii\db\Migration;

/**
 * Class m250305_103548_add_badge_count_to_push_tokens
 */
class m250305_103548_add_badge_count_to_push_tokens extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('push_notification', 'badge_count', $this->integer()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250305_103548_add_badge_count_to_push_tokens cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250305_103548_add_badge_count_to_push_tokens cannot be reverted.\n";

        return false;
    }
    */
}
