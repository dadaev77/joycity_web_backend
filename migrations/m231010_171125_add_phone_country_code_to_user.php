<?php

use yii\db\Migration;

/**
 * Class m231010_171125_add_phone_country_code_to_user
 */
class m231010_171125_add_phone_country_code_to_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user', 'phone_country_code', $this->string(10));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m231010_171125_add_phone_country_code_to_user cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m231010_171125_add_phone_country_code_to_user cannot be reverted.\n";

        return false;
    }
    */
}
