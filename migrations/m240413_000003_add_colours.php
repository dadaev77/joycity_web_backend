<?php

use yii\db\Migration;

/**
 * Handles the creation of table `colour`.
 */
class m240413_000003_add_colours extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
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
        $this->delete('colour');
    }
} 