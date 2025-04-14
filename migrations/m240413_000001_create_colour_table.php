<?php

use yii\db\Migration;

/**
 * Handles the creation of table `colour`.
 */
class m240413_000001_create_colour_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('colour', [
            'colour_id' => $this->primaryKey(),
            'name' => $this->string(11)->notNull(),
        ]);

        // Добавляем предопределенные цвета
        $colours = [
            'Черный',
            'Серый',
            'Белый',
            'Красный',
            'Оранжевый',
            'Желтый',
            'Зеленый',
            'Синий',
            'Фиолетовый',
            'Розовый'
        ];

        foreach ($colours as $colour) {
            $this->insert('colour', ['name' => $colour]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('colour');
    }
} 