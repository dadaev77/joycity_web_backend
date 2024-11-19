<?php

use yii\db\Migration;

/**
 * Class m230904_160944_implementing_feedback_count
 */
class m230904_160944_implementing_feedback_count extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'product',
            'feedback_count',
            $this->integer()
                ->notNull()
                ->defaultValue(0)
                ->after('rating')
        );
        $this->addColumn(
            'user',
            'feedback_count',
            $this->integer()
                ->notNull()
                ->defaultValue(0)
                ->after('rating_buyer')
        );

        $this->renameColumn('user', 'rating_buyer', 'rating');
        $this->alterColumn(
            'user',
            'rating',
            $this->float()
                ->notNull()
                ->defaultValue(0)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230904_160944_implementing_feedback_count cannot be reverted.\n";

        return false;
    }
}
