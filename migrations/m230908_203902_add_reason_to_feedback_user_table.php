<?php

use yii\db\Migration;

/**
 * Class m230908_203902_add_reason_to_feedback_user_table
 */
class m230908_203902_add_reason_to_feedback_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'feedback_user',
            'reason',
            $this->string(255)->notNull()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230908_203902_add_reason_to_feedback_user_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230908_203902_add_reason_to_feedback_user_table cannot be reverted.\n";

        return false;
    }
    */
}
