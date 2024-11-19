<?php

use yii\db\Migration;

/**
 * Class m230828_231718_change_user_role
 */
class m230828_231718_change_user_role extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            'user',
            'role',
            $this->tinyInteger()
                ->notNull()
                ->defaultValue(0)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230828_231718_change_user_role cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230828_231718_change_user_role cannot be reverted.\n";

        return false;
    }
    */
}
