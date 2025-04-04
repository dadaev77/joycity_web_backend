<?php

namespace app\controllers\api\v1\internal\constants;

use app\controllers\api\v1\InternalController;
use app\components\ApiResponse;
use Yii;

class RolePermissionController extends InternalController
{

    public function actionIndex()
    {
        return ApiResponse::info([
            'name' => 'RolePermissionController',
            'description' => 'Контроллер для управления ролями и разрешениями из внутреннего API',
            'actions' => [
                'create-role' => 'Создать новую роль',
                'create-permission' => 'Создать новое разрешение',
                'add-permission-to-role' => 'Добавить разрешение к роли',
                'remove-permission-from-role' => 'Удалить разрешение из роли',
                'get-permissions' => 'Получить все разрешения',
            ],
        ]);
    }

    public function actionCreateRole()
    {
        try {
            $name = Yii::$app->request->post('name');
            $description = Yii::$app->request->post('description');
            $role = \app\services\PermissionControlService::createRole($name, $description);
            return $role;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function actionCreatePermission()
    {
        try {
            $name = Yii::$app->request->post('name');
            $description = Yii::$app->request->post('description');
            $permission = \app\services\PermissionControlService::createPermission($name, $description);
            return $permission;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function actionAddPermissionToRole()
    {
        try {
            $roleName = Yii::$app->request->post('name');
            $permissionName = Yii::$app->request->post('permission');
            $role = \app\models\RoleModel::findOne(['name' => $roleName]);
            $permission = \app\models\PermissionModel::findOne(['name' => $permissionName]);
            $result = \app\services\PermissionControlService::addPermissionToRole($role->id, $permission->id);
            return $result;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function actionRemovePermissionFromRole()
    {
        $roleName = Yii::$app->request->post('name');
        $permissionName = Yii::$app->request->post('permission');
        $role = \app\models\RoleModel::findOne(['name' => $roleName]);
        $permission = \app\models\PermissionModel::findOne(['name' => $permissionName]);
        $result = \app\services\PermissionControlService::removePermissionFromRole($role->id, $permission->id);
        return $result;
    }

    public function actionGetPermissions()
    {
        try {
            $permissions = \app\services\PermissionControlService::getPermissions();
            return $permissions;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
