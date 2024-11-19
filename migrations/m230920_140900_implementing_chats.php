<?php

use app\models\User;
use yii\db\Exception as DBException;
use yii\db\Migration;

/**
 * Class m230920_140900_implementing_chats
 */
class m230920_140900_implementing_chats extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'user',
            'personal_id',
            $this->string()
                ->notNull()
                ->after('access_token')
        );

        foreach (User::find()->each() as $user) {
            $user->personal_id = md5(time() . random_int(1e3, 9e3));

            if (!$user->save(false)) {
                throw new DBException(
                    'Error update user ' .
                        json_encode(
                            $user->getFirstErrors(),
                            JSON_THROW_ON_ERROR
                        )
                );
            }
        }

        $this->createIndex('idx_user_personal_id', 'user', 'personal_id', true);

        $this->createTable('chat', [
            'id' => $this->primaryKey(),
            'created_at' => $this->dateTime()->notNull(),
            'twilio_id' => $this->string()->notNull(),
            'name' => $this->string()
                ->notNull()
                ->defaultValue(''),
            'group' => $this->string()->notNull(),
            'type' => $this->string()->notNull(),
            'order_id' => $this->integer(),
            'user_verification_request_id' => $this->integer(),
            'is_archive' => $this->boolean()
                ->notNull()
                ->defaultValue(0),
        ]);
        $this->addForeignKey(
            'fk_chat_order_id',
            'chat',
            'order_id',
            'order',
            'id'
        );
        $this->createIndex(
            'fk_chat_user_verification_request_id',
            'chat',
            'user_verification_request_id',
            true
        );
        $this->addForeignKey(
            'fk_chat_user_verification_request_id',
            'chat',
            'user_verification_request_id',
            'user_verification_request',
            'id'
        );

        $this->createTable('chat_user', [
            'id' => $this->primaryKey(),
            'chat_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
        ]);
        $this->addForeignKey(
            'fk_chat_user_chat_id',
            'chat_user',
            'chat_id',
            'chat',
            'id'
        );
        $this->addForeignKey(
            'fk_chat_user_user_id',
            'chat_user',
            'user_id',
            'user',
            'id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230920_140900_implementing_chats cannot be reverted.\n";

        return false;
    }
}
