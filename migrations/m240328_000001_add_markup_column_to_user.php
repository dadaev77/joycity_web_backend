<?php

use yii\db\Migration;

class m240328_000001_add_markup_column_to_user extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%user}}')->getColumn('markup') === null) {
            $this->addColumn('{{%user}}', 'markup', $this->integer()->null());
            
            // Устанавливаем значение по умолчанию 5 ТОЛЬКО для клиентов
            $this->update('{{%user}}', 
                ['markup' => 5], 
                ['role' => 'client'] // здесь правильно, только для role = client
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->getTableSchema('{{%user}}')->getColumn('markup') !== null) {
            $this->dropColumn('{{%user}}', 'markup');
        }
    }
}