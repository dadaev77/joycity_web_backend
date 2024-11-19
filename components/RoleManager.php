<?php

namespace app\components;

use Yii;
use yii\rbac\DbManager;

class RoleManager
{
    public static function assignRole($user, $role)
    {
        $auth = Yii::$app->authManager;
        $role = $auth->getRole($role);
        $auth->assign($role, $user->id);
    }
}
