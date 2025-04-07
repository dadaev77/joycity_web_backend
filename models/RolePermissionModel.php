<?php

namespace app\models;

use Yii;
use Throwable;

class RolePermissionModel extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'roles_permissions';
    }

    public function beforeSave($insert)
    {
        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes)
    {
        return parent::afterSave($insert, $changedAttributes);
    }

    public function rules()
    {
        return [
            [['role_id', 'permission_id'], 'required'],
            [['role_id', 'permission_id'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'role_id' => 'Роль',
            'permission_id' => 'Разрешение',
        ];
    }
}
