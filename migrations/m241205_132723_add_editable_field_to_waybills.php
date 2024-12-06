<?php

use yii\db\Migration;

/**
 * Class m241205_132723_add_editable_field_to_waybills
 */
class m241205_132723_add_editable_field_to_waybills extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('waybill', 'editable', $this->boolean()->defaultValue(true)->comment('Флаг редактируемости накладной'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('waybills', 'editable');
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241205_132723_add_editable_field_to_waybills cannot be reverted.\n";

        return false;
    }
    */
}
