<?php

use yii\db\Migration;

/**
 * Class m230704_150105_alter_product_table
 */
class m230704_150105_alter_product_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        // Добавление новых столбцов
        $this->addColumn(
            'product',
            'lot_size_level_one',
            'varchar(45) NOT NULL'
        );
        $this->addColumn(
            'product',
            'lot_size_price_level_one',
            'decimal NOT NULL'
        );
        $this->addColumn('product', 'lot_size_level_two', 'varchar(45) NULL');
        $this->addColumn('product', 'lot_size_price_level_two', 'decimal NULL');
        $this->addColumn('product', 'lot_size_level_three', 'varchar(45) NULL');
        $this->addColumn(
            'product',
            'lot_size_price_level_three',
            'decimal NULL'
        );
        $this->addColumn('product', 'lot_size_level_four', 'varchar(45) NULL');
        $this->addColumn(
            'product',
            'lot_size_price_level_four',
            'decimal NULL'
        );

        // Копирование значений из старых столбцов в новые столбцы
        $this->execute(
            'UPDATE product SET lot_size_level_one = lot_size, lot_size_price_level_one = lot_size_price'
        );

        // Удаление старых столбцов
        $this->dropColumn('product', 'lot_size');
        $this->dropColumn('product', 'lot_size_price');
    }
}
