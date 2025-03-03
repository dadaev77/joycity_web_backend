<?php

use yii\db\Migration;

/**
 * Class m240328_000001_add_markup_percentage_to_user
 */
class m240328_000001_add_markup_percentage_to_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'markup_percentage', $this->decimal(5, 2)->defaultValue(5.00)->notNull());
        
        // Установка значения по умолчанию для существующих клиентов
        $this->update('{{%user}}', ['markup_percentage' => 5.00], ['role' => 'client']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'markup_percentage');
    }
} 