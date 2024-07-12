<?php

use yii\db\Migration;

/**
 * Class m230712_144119_add_required_fields_to_user_table
 */
class m230712_144119_add_required_fields_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            '{{%user}}',
            'phone_number',
            $this->integer(12)->notNull()
        );
        $this->alterColumn(
            '{{%user}}',
            'organization_name',
            $this->string(60)->notNull()
        );
        $this->alterColumn(
            '{{%user}}',
            'surname',
            $this->string(60)->notNull()
        );
        $this->alterColumn('{{%user}}', 'name', $this->string(60)->notNull());
    }

    public function safeDown()
    {
        $this->alterColumn(
            '{{%user}}',
            'phone_number',
            $this->integer(12)->null()
        );
        $this->alterColumn(
            '{{%user}}',
            'organization_name',
            $this->string(60)->null()
        );
        $this->alterColumn('{{%user}}', 'surname', $this->string(60)->null());
        $this->alterColumn('{{%user}}', 'name', $this->string(60)->null());
    }
}
