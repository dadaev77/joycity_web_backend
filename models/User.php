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
    public const ROLE_CLIENT_DEMO = 'client-demo';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_BUYER = 'buyer';
    public const ROLE_BUYER_DEMO = 'buyer-demo';
    public const ROLE_FULFILLMENT = 'fulfillment';

    public const ROLES_ALL = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_CLIENT,
        self::ROLE_CLIENT_DEMO,
        self::ROLE_MANAGER,
        self::ROLE_BUYER,
        self::ROLE_BUYER_DEMO,
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

    public function getRole()
    {
        return $this->hasOne(RoleModel::class, ['id' => 'role_id'])->one();
    }

    public function is(array $roles): bool
    {
        return in_array($this->getRole()->name, $roles);
    }

    /**
     * Check if a user has a permission
     * @param string $permissionName The name of the permission
     * @return bool True if the user has the permission, false otherwise
     */
    public function can(string $permissionName)
    {
        $allow = $this->getPermissions()->where(['name' => $permissionName])->one();

        if (!$allow) {
            return false;
        }

        return true;
    }


    public function getPermissions()
    {
        return $this->hasMany(\app\models\PermissionModel::class, ['id' => 'permission_id'])
            ->viaTable('roles_permissions', ['role_id' => 'role_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey): ?bool
    {
        return $this->access_token === $authKey;
    }

    public function getSettings()
    {
        return $this->hasOne(UserSettings::class, ['user_id' => 'id'])->one();
    }

    public function getRandomManager()
    {
        $users = User::find()->all();
        $users = array_filter($users, function ($user) {
            return $user->is([self::ROLE_MANAGER]);
        });
        if (empty($users)) {
            return null;
        }
        $randomUser = $users[array_rand($users)];

        return $randomUser;
    }
}
