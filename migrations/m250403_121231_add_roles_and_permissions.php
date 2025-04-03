<?php

use yii\db\Migration;

/**
 * Class m250403_121231_add_roles_and_permissions
 */
class m250403_121231_add_roles_and_permissions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%roles}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'description' => $this->string()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->createTable('{{%permissions}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'description' => $this->string()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->createTable('{{%roles_permissions}}', [
            'role_id' => $this->integer()->notNull(),
            'permission_id' => $this->integer()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        
        $this->addForeignKey('fk_roles_permissions_role_id', '{{%roles_permissions}}', 'role_id', '{{%roles}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_roles_permissions_permission_id', '{{%roles_permissions}}', 'permission_id', '{{%permissions}}', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250403_121231_add_roles_and_permissions cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250403_121231_add_roles_and_permissions cannot be reverted.\n";

        return false;
    }
    */
}
