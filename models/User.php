<?php

namespace app\models;

use app\models\responseCodes\UserCodes;
use app\models\structure\UserStructure;
use Yii;
use yii\db\ActiveRecordInterface;
use yii\web\IdentityInterface;

class User extends UserStructure implements IdentityInterface
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPER_ADMIN = 'super-admin';
    public const ROLE_CLIENT = 'client';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_BUYER = 'buyer';
    public const ROLE_FULFILLMENT = 'fulfillment';

    public const ROLES_ALL = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_CLIENT,
        self::ROLE_MANAGER,
        self::ROLE_BUYER,
        self::ROLE_FULFILLMENT,
    ];

    public const SCENARIO_PROFILE_CREATION = 'userProfileCreation';

    public static function findIdentity($id): array|ActiveRecordInterface
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        $user = self::find()
            ->where(['access_token' => $token])
            ->one();

        if ($user) {
            return $user;
        }

        // return null;
        return [];
    }

    public static function getIdentity(): mixed
    {
        if (Yii::$app->user->getIdentity()) {
            return Yii::$app->user->getIdentity();
        }

        return null;
    }

    public static function apiCodes(): UserCodes
    {
        return UserCodes::getStatic();
    }

    public function getId(): int|string
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey(): ?string
    {
        return $this->access_token;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey): ?bool
    {
        return $this->access_token === $authKey;
    }
}
