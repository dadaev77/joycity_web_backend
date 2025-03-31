<?php

use yii\db\Migration;

/**
 * Class m250320_140224_create_charges_table
 */
class m250320_140224_create_charges_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('charges', [
            'id' => $this->primaryKey(),
            'usd_charge' => $this->integer()->notNull()->defaultValue(2)->check('usd_charge <= 100'),
            'cny_charge' => $this->integer()->notNull()->defaultValue(5)->check('cny_charge <= 100'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Вставляем начальные значения
        $this->insert('charges', [
            'usd_charge' => 2,
            'cny_charge' => 5,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('charges');
    }
}
