<?php

use yii\db\Migration;

/**
 * Class m230707_081840_add_rating_buyer_to_user_table
 */
class m230707_081840_add_rating_buyer_to_user_table extends Migration
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
        $this->addColumn('user', 'rating_buyer', $this->string(45)->null());
    }

    public function down()
    {
        $this->dropColumn('user', 'rating_buyer');
    }
}
