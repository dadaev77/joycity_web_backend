<?php

use yii\db\Migration;

/**
 * Обновляет значения delivery_time_days для типов доставки
 */
class m240000_000001_update_delivery_time_days extends Migration
{
    public function up()
    {
        // Сначала добавляем колонку
        $this->addColumn('type_delivery', 'delivery_time_days', $this->integer()->null());

        // Затем обновляем значения
        $this->update('type_delivery', 
            ['delivery_time_days' => 25],
            ['id' => 8] // Медленное авто
        );

        $this->update('type_delivery', 
            ['delivery_time_days' => 16],
            ['id' => 9] // Быстрое авто
        );
    }

    public function down()
    {
        // Удаляем колонку
        $this->dropColumn('type_delivery', 'delivery_time_days');
    }
} 