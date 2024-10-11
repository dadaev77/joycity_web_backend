<?php

use yii\db\Migration;

/**
 * Class m241011_134849_add_fields_to_user
 */
class m241011_134849_add_fields_to_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user', 'uuid', $this->string(16)->unique());
        $this->addColumn('user', 'telegram', $this->string(100)->unique());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241011_134849_add_fields_to_user cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241011_134849_add_fields_to_user cannot be reverted.\n";

        return false;
    }
    */
}
