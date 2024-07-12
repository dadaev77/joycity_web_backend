<?php

use yii\db\Migration;

/**
 * Class m240205_150507_fulfillment_improvements
 */
class m240205_150507_fulfillment_improvements extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            'user_settings',
            'high_workload',
            $this->boolean()->notNull()->defaultValue(0),
        );

        $this->alterColumn(
            'order',
            'fulfillment_id',
            $this->integer()->after('manager_id'),
        );

        $this->createIndex(
            'fk-fulfillment_stock_report-order_id',
            'fulfillment_stock_report',
            'order_id',
            true,
        );

        $this->dropIndex(
            'idx-fulfillment_stock_report-order_id',
            'fulfillment_stock_report',
        );

        $this->createIndex(
            'fk-fulfillment_inspection_report-order_id',
            'fulfillment_inspection_report',
            'order_id',
            true,
        );

        $this->dropIndex(
            'idx-fulfillment_inspection_report-order_id',
            'fulfillment_inspection_report',
        );

        $this->createIndex(
            'fk-fulfillment_packaging_labeling-order_id',
            'fulfillment_packaging_labeling',
            'order_id',
            true,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240205_150507_fulfillment_improvements cannot be reverted.\n";

        return false;
    }
}
