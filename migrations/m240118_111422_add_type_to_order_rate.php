<?php

use app\models\OrderRate;
use yii\db\Migration;

/**
 * Class m240118_111422_add_type_to_order_rate
 */
class m240118_111422_add_type_to_order_rate extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('order_rate', 'type', $this->string()->notNull());

        $this->update('order_rate', [
            'type' => OrderRate::TYPE_PRODUCT_PAYMENT,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('order_rate', 'type');
    }
}
