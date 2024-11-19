<?php

use yii\db\Migration;

/**
 * Class m231102_140050_add_created_at_rate
 */
class m231102_140050_add_created_at_rate extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'rate',
            'created_at',
            $this->datetime()
                ->notNull()
                ->defaultExpression('CURRENT_TIMESTAMP'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('rate', 'created_at');
    }
}
