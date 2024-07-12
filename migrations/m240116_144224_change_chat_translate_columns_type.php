<?php

use yii\db\Migration;

/**
 * Class m240116_144224_change_chat_translate_columns_type
 */
class m240116_144224_change_chat_translate_columns_type extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('chat_translate', 'ru', $this->text()->notNull());
        $this->alterColumn('chat_translate', 'zh', $this->text()->notNull());
        $this->alterColumn('chat_translate', 'en', $this->text()->notNull());
    }
}
