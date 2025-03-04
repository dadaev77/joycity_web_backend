<?php

use yii\db\Migration;

class m240328_000001_add_markup_column_to_user extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%user}}')->getColumn('markup') === null) {
            $this->addColumn('{{%user}}', 'markup', $this->float()->null());
        }
    }

    public function safeDown()
    {
        if ($this->db->getTableSchema('{{%user}}')->getColumn('markup') !== null) {
            $this->dropColumn('{{%user}}', 'markup');
        }
    }
}