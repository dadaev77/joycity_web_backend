<?php

use yii\db\Migration;

/**
 * Class m230912_095547_delete_column_type_feedback_user_link_attachment
 */
class m230912_095547_delete_column_type_feedback_user_link_attachment extends
    Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('feedback_user_link_attachment', 'type');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230912_095547_delete_column_type_feedback_user_link_attachment cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230912_095547_delete_column_type_feedback_user_link_attachment cannot be reverted.\n";

        return false;
    }
    */
}
