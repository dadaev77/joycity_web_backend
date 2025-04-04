<?php

namespace app\models;

use \yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\models\RoleModel;

class PermissionModel extends ActiveRecord
{

    public static function tableName()
    {
        return 'permissions';
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
            'name' => 'Название разрешения',
            'description' => 'Описание разрешения',
        ];
    }

    public function getRoles()
    {
        return $this->hasMany(RoleModel::class, ['id' => 'role_id'])
            ->viaTable('roles_permissions', ['permission_id' => 'id']);
    }
}
