<?php

namespace app\components;

use Yii;
use yii\base\ActionFilter;
use yii\web\ForbiddenHttpException;

class PermissionFilter extends ActionFilter
{
    public function beforeAction($action)
    {
        $user = Yii::$app->user;

        $permission = $action->controller->id . '-' . $action->id;

        $parts = explode('/', $permission);
        $lastPart = end($parts);
        $permission = $lastPart;
        var_dump($permission);
        die();
        if (!$user->can($permission)) {
            throw new ForbiddenHttpException('Доступ запрещён');
        }

        return parent::beforeAction($action);
    }
}
