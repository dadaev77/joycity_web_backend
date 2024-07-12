<?php

use yii\db\Migration;

/**
 * Class m230730_150549_add_delete_in_to_user_table
 */
class m230730_150549_add_delete_in_to_user_table extends Migration
{
    public function up()
    {
        $this->addColumn(
            'user',
            'is_deleted',
            $this->integer()->defaultValue(0)
        );
    }
}
