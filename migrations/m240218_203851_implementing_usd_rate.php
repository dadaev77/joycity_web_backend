<?php

use yii\db\Migration;

/**
 * Class m240218_203851_implementing_usd_rate
 */
class m240218_203851_implementing_usd_rate extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'rate',
            'USD',
            $this->decimal(10, 4)
                ->notNull()
                ->after('CNY'),
        );
        $this->addColumn(
            'order_rate',
            'USD',
            $this->decimal(10, 4)
                ->notNull()
                ->after('CNY'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('rate', 'USD');
        $this->dropColumn('order_rate', 'USD');

        return false;
    }
}
