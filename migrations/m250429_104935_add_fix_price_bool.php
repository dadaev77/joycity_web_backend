<?php

use yii\db\Migration;

/**
 * Class m250429_104935_add_fix_price_bool
 */
class m250429_104935_add_fix_price_bool extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%order}}', 'fix_price', $this->boolean()->defaultValue(false)->after('service_markup_sum'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%order}}', 'fix_price');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250429_104935_add_fix_price_bool cannot be reverted.\n";

        return false;
    }
    */
}
