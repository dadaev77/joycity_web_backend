<?php

use yii\db\Migration;

/**
 * Class m250304_131636_add_platform_to_push_service
 */
class m250304_131636_add_platform_to_push_service extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('push_notification', 'operating_system', $this->string()->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250304_131636_add_platform_to_push_service cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250304_131636_add_platform_to_push_service cannot be reverted.\n";

        return false;
    }
    */
}
