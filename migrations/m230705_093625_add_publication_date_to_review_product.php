<?php

use yii\db\Migration;

/**
 * Class m230705_093625_add_publication_date_to_review_product
 */
class m230705_093625_add_publication_date_to_review_product extends Migration
{
    public function up()
    {
        $this->addColumn(
            'review_product',
            'publication_date',
            $this->date()->null()
        );
    }

    public function down()
    {
        $this->dropColumn('review_product', 'publication_date');
    }
}
