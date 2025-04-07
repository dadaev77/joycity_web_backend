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
                'delete-role' => 'Удалить роль',
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

    public function actionDeleteRole()
    {
        $name = Yii::$app->request->post('name');
        if (!$name) {
            return [
                'status' => 'error',
                'message' => 'Имя роли не указано',
            ];
        }
        $role = \app\services\PermissionControlService::deleteRole($name);
        return $role;
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
            if (!$role || !$permission) {
                return [
                    'status' => 'error',
                    'message' => 'Роль или разрешение не найдены',
                ];
            }
            $existingPermission = \app\models\RolePermissionModel::findOne(['role_id' => $role->id, 'permission_id' => $permission->id]);
            if ($existingPermission) {
                return [
                    'status' => 'error',
                    'message' => 'Разрешение уже существует',
                ];
            }
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
        if (!$role || !$permission) {
            return [
                'status' => 'error',
                'message' => 'Роль или разрешение не найдены',
            ];
        }
        $existingPermission = \app\models\RolePermissionModel::findOne(['role_id' => $role->id, 'permission_id' => $permission->id]);
        if (!$existingPermission) {
            return [
                'status' => 'error',
                'message' => 'Разрешение не найдено',
            ];
        }
        $result = \app\services\PermissionControlService::removePermissionFromRole($role->id, $permission->id);
        if (!$result) {
            return [
                'status' => 'error',
                'message' => 'Не удалось удалить разрешение',
            ];
        }
        return [
            'status' => 'success',
            'message' => 'Разрешение удалено',
        ];
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
