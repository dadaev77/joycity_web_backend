<?php

namespace app\models;

use \yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class RoleModel extends ActiveRecord
{

    public static function tableName()
    {
        return 'roles';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['name', 'description'], 'required'],
            [['name', 'description'], 'string', 'max' => 255],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name' => 'Название роли',
            'description' => 'Описание роли',
        ];
    }

    public function getPermissions()
    {
        return $this->hasMany(PermissionModel::class, ['id' => 'permission_id'])
            ->viaTable('roles_permissions', ['role_id' => 'id']);
    }
    public function getUsers()
    {
        return $this->hasMany(User::class, ['role_id' => 'id']);
    }

    public function setNewPermissions(array $permissions)
    {
        $permissions = $this->permissions;
        foreach ($permissions as $permission) {
            $this->detach($permission);
        }
        $notFoundPermissions = [];
        foreach ($permissions as $permission) {
            $permission = \app\models\PermissionModel::findOne(['name' => $permission]);
            if ($permission) {
                $this->link('permissions', $permission);
            } else {
                $notFoundPermissions[] = $permission;
            }
        }
        if (count($notFoundPermissions) > 0) {
            return $notFoundPermissions;
        }
        return true;
    }
}
