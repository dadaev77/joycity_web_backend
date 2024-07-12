<?php

use yii\db\Migration;

/**
 * Class m230718_093515_change_phone_number_in_user_table
 */
class m230718_093515_change_phone_number_in_user_table extends Migration
{
    public function up()
    {
        $this->alterColumn(
            'user',
            'phone_number',
            $this->string(40)->notNull()
        );
    }

    public function down()
    {
        $this->alterColumn(
            'user',
            'phone_number',
            $this->integer(12)->notNull()
        );
    }
}
