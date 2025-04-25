<?php

namespace app\models;

use \yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class RoleModel extends ActiveRecord
{
    public $role_permissions;
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
            [['name'], 'unique'],
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

    public function getRoleName()
    {
        return $this->name;
    }
}
