<?php

use yii\db\Migration;

/**
 * Class m230828_171014_remove_profile_picture
 */
class m230828_171014_remove_profile_picture extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('user', 'profile_picture');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230828_171014_remove_profile_picture cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230828_171014_remove_profile_picture cannot be reverted.\n";

        return false;
    }
    */
}
