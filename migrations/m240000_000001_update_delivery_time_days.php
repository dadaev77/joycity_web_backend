<?php

use yii\db\Migration;

/**
 * Обновляет значения delivery_time_days для типов доставки
 */
class m240000_000001_update_delivery_time_days extends Migration
{
    public function up()
    {
        // Обновляем время доставки для медленного авто (25 дней)
        $this->update('type_delivery', 
            ['delivery_time_days' => 25],
            ['id' => 8] // Медленное авто
        );

        // Обновляем время доставки для быстрого авто (16 дней)
        $this->update('type_delivery', 
            ['delivery_time_days' => 16],
            ['id' => 9] // Быстрое авто
        );
    }

    public function down()
    {
        // Возвращаем значения в NULL
        $this->update('type_delivery', 
            ['delivery_time_days' => null],
            ['id' => [8, 9]]
        );
    }
} 