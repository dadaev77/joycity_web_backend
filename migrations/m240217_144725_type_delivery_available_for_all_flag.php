<?php

use yii\db\Migration;

/**
 * Class m240217_144725_type_delivery_available_for_all_flag
 */
class m240217_144725_type_delivery_available_for_all_flag extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'type_delivery',
            'available_for_all',
            $this->boolean()
                ->notNull()
                ->defaultValue(0),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('type_delivery', 'available_for_all');
    }
}
