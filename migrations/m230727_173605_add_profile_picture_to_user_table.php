<?php

use yii\db\Migration;

/**
 * Class m230727_173605_add_profile_picture_to_user_table
 */
class m230727_173605_add_profile_picture_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        // Добавление нового поля `profile_picture`
        $this->addColumn('user', 'profile_picture', $this->string(255));
    }
}
