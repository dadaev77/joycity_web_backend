<?php

use yii\db\Migration;

/**
 * Class m240111_171450_add_user_id_to_delivery_point_address
 */
class m240111_171450_add_user_id_to_delivery_point_address extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('delivery_point_address', 'user_id', $this->integer());

        $this->addForeignKey(
            'fk_delivery_point_address_user_id',
            'delivery_point_address',
            'user_id',
            'user',
            'id',
        );

        $this->createIndex(
            'idx_unique_user_id_delivery_point_address',
            'delivery_point_address',
            'user_id',
            true,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex(
            'idx_unique_user_id_delivery_point_address',
            'delivery_point_address',
        );

        $this->dropForeignKey(
            'fk_delivery_point_address_user_id',
            'delivery_point_address',
        );

        $this->dropColumn('delivery_point_address', 'user_id');
    }
}
