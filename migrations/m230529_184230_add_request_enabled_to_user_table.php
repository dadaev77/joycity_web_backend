<?php

use yii\db\Migration;

/**
 * Class m230529_184230_add_request_enabled_to_user_table
 */
class m230529_184230_add_request_enabled_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'user',
            'request_enabled',
            $this->integer()
                ->notNull()
                ->defaultValue(0)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('user', 'request_enabled');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230529_184230_add_request_enabled_to_user_table cannot be reverted.\n";

        return false;
    }
    */
}
