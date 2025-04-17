<?php

use yii\db\Migration;

/**
 * Class m250131_120000_add_markup_fields_to_order
 */
class m250131_120000_add_markup_fields_to_order extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('order', 'service_markup', $this->float()->notNull()->defaultValue(0));
        $this->addColumn('order', 'service_markup_sum', $this->float()->notNull()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('order', 'service_markup');
        $this->dropColumn('order', 'service_markup_sum');
    }
} 