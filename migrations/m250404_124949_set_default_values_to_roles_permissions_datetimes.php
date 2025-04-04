<?php

use yii\db\Migration;

/**
 * Class m250404_124949_set_default_values_to_roles_permissions_datetimes
 */
class m250404_124949_set_default_values_to_roles_permissions_datetimes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('roles', 'created_at', $this->dateTime()->defaultValue(new \yii\db\Expression('CURRENT_TIMESTAMP')));
        $this->alterColumn('roles', 'updated_at', $this->dateTime()->defaultValue(new \yii\db\Expression('CURRENT_TIMESTAMP')));
        $this->alterColumn('permissions', 'created_at', $this->dateTime()->defaultValue(new \yii\db\Expression('CURRENT_TIMESTAMP')));
        $this->alterColumn('permissions', 'updated_at', $this->dateTime()->defaultValue(new \yii\db\Expression('CURRENT_TIMESTAMP')));
        $this->alterColumn('roles_permissions', 'created_at', $this->dateTime()->defaultValue(new \yii\db\Expression('CURRENT_TIMESTAMP')));
        $this->alterColumn('roles_permissions', 'updated_at', $this->dateTime()->defaultValue(new \yii\db\Expression('CURRENT_TIMESTAMP')));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250404_124949_set_default_values_to_roles_permissions_datetimes cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250404_124949_set_default_values_to_roles_permissions_datetimes cannot be reverted.\n";

        return false;
    }
    */
}
