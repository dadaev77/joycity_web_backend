<?php

use app\models\User;
use app\models\UserVerificationRequest;
use yii\db\Exception as DatabaseException;
use yii\db\Migration;

/**
 * Class m230926_124232_adding_manager_id_to_user_verification_request
 */
class m230926_124232_adding_manager_id_to_user_verification_request extends
    Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'user_verification_request',
            'manager_id',
            $this->integer()
                ->notNull()
                ->after('created_by_id'),
        );

        $manager = User::findOne(['role' => User::ROLE_MANAGER]);

        foreach (UserVerificationRequest::find()->each() as $form) {
            if (!$manager) {
                throw new DatabaseException('Cannot find manager');
            }

            $form->manager_id = $manager->id;

            if (!$form->save()) {
                throw new DatabaseException(
                    'Error update form, ' .
                        json_encode(
                            $form->getFirstErrors(),
                            JSON_THROW_ON_ERROR,
                        ),
                );
            }
        }

        $this->addForeignKey(
            'fk_user_verification_request_manager_id',
            'user_verification_request',
            'manager_id',
            'user',
            'id',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230926_124232_adding_manager_id_to_user_verification_request cannot be reverted.\n";

        return false;
    }
}
