<?php

use yii\db\Migration;

/**
 * Class m230813_232557_change_user_nickname_column
 */
class m230813_232557_change_user_nickname_column extends Migration
{
    public function up()
    {
        $this->alterColumn('user', 'nickname', $this->string(100)->null());
        $this->alterColumn('user', 'email', $this->string(100)->null());
    }
}
