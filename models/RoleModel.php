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
        return $this->hasMany(PermissionModel::class, ['id' => 'permission_id'])->via('roles_permissions');
    }
}
