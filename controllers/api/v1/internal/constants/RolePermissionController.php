<?php

namespace app\controllers\api\v1\internal\constants;

use app\controllers\api\v1\InternalController;
use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\services\rolePermissions\RoleService;
use app\services\rolePermissions\PermissionService;
use app\services\rolePermissions\RolePermissionService;
use Yii;

class RolePermissionController extends InternalController
{


    public function behaviours()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions'] = [
            'create-role' => ['post'],
            'create-permission' => ['post'],
            'delete-role' => ['delete'],
            'add-permission-to-role' => ['post'],
            'remove-permission-from-role' => ['post'],
            'get-permissions' => ['get'],
            'get-roles' => ['get'],
            'get-role' => ['get'],
            'update-role' => ['put'],
            'delete-role' => ['delete'],
        ];
        return $behaviors;
    }

    public function actionIndex()
    {
        return ApiResponse::info([
            'name' => 'RolePermissionController',
            'description' => 'Контроллер для управления ролями и разрешениями из внутреннего API',
            'actions' => [
                'get-roles' => 'Получить все роли [ ?limit= 10/50]',
                'get-permissions' => 'Получить все разрешения [?limit= 10/50]',
                'create-role' => 'Создать новую роль',
                'delete-role' => 'Удалить роль',
                'create-permission' => 'Создать новое разрешение',
                'add-permission-to-role' => 'Добавить разрешение к роли',
                'remove-permission-from-role' => 'Удалить разрешение из роли',
            ],
        ]);
    }

    public function actionCreateRole()
    {
        try {
            $name = Yii::$app->request->post('name');
            $description = Yii::$app->request->post('description');
            $role = RoleService::createRole($name, $description);
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
            $permission = PermissionService::createPermission($name, $description);
            return $permission;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    public function actionDeleteRole($id)
    {
        $role = RoleService::deleteRole($id);
        if (!$role) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST, ['message' => 'Роль не найдена',]);
        return ApiResponse::byResponseCode(ResponseCodes::getStatic()->SUCCESS, ['message' => 'Роль удалена',]);
    }



    public function actionAddPermissionToRole()
    {

        $roleName = Yii::$app->request->post('name');
        $permissionName = Yii::$app->request->post('permission');
        $role = \app\models\RoleModel::findOne(['name' => $roleName]);
        $permission = \app\models\PermissionModel::findOne(['name' => $permissionName]);
        if (!$role) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST, ['message' => 'Роль не найдена',]);
        if (!$permission) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST, ['message' => 'Разрешение не найдено',]);
        $existingPermission = \app\models\RolePermissionModel::findOne(['role_id' => $role->id, 'permission_id' => $permission->id]);
        if ($existingPermission) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST, ['message' => 'Разрешение уже существует',]);
        $result = RolePermissionService::addPermissionToRole($role->id, $permission->id);
        return ApiResponse::byResponseCode(ResponseCodes::getStatic()->SUCCESS, $result);
    }

    public function actionRemovePermissionFromRole()
    {
        $roleName = Yii::$app->request->post('name');
        $permissionName = Yii::$app->request->post('permission');
        $role = \app\models\RoleModel::findOne(['name' => $roleName]);
        $permission = \app\models\PermissionModel::findOne(['name' => $permissionName]);
        if (!$role) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST, ['message' => 'Роль не найдена',]);
        if (!$permission) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST, ['message' => 'Разрешение не найдено',]);
        $existingPermission = \app\models\RolePermissionModel::findOne(['role_id' => $role->id, 'permission_id' => $permission->id]);
        if (!$existingPermission) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST, ['message' => 'Разрешение не найдено',]);
        $result = RolePermissionService::removePermissionFromRole($role->id, $permission->id);
        if (!$result) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST, ['message' => 'Не удалось удалить разрешение',]);
        return ApiResponse::byResponseCode(ResponseCodes::getStatic()->SUCCESS, ['message' => 'Разрешение удалено',]);
    }

    public function actionGetPermissions($page = 1, $limit = 10, $query = null)
    {
        $offset = ($page - 1) * $limit;
        $permissions = \app\models\PermissionModel::find()
            ->limit($limit)
            ->offset($offset)
            ->asArray()
            ->with('roles')
            ->all();

        if ($query) $permissions = \app\models\PermissionModel::find()
            ->where(['like', 'name', $query])
            ->asArray()->with('roles')->all();

        $totalCount = count($permissions);
        $totalPages = ceil($totalCount / $limit);

        return ApiResponse::byResponseCode(ResponseCodes::getStatic()->SUCCESS, [
            'permissions' => $permissions,
            'total_pages' => $totalPages,
            'current_page' => $page,
        ]);
    }

    public function actionGetRoles($page = 1, $limit = 10, $query = null)
    {
        $offset = ($page - 1) * $limit;
        $roles = \app\models\RoleModel::find()
            ->limit($limit)
            ->offset($offset)
            ->asArray()
            ->with('permissions')->all();

        if ($query) $roles = \app\models\RoleModel::find()
            ->where(['like', 'name', $query])
            ->asArray()
            ->with('permissions')->all();

        $totalCount = count($roles);
        $totalPages = ceil($totalCount / $limit);

        return ApiResponse::byResponseCode(ResponseCodes::getStatic()->SUCCESS, [
            'roles' => $roles,
            'total_pages' => $totalPages,
            'current_page' => $page,
        ]);
    }

    public function actionGetRole($id)
    {
        if (!$id) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST, ['message' => 'Идентификатор роли не указан',]);
        $role = \app\models\RoleModel::find()->where(['id' => $id])->with('permissions')->asArray()->one();
        if (!$role) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST, ['message' => 'Роль не найдена',]);
        return ApiResponse::byResponseCode(ResponseCodes::getStatic()->SUCCESS, ['role' => $role]);
    }
    public function actionUpdateRole($id)
    {
        $data = Yii::$app->request->post();
        $role = RoleService::updateRole($id, $data);
        if (!$role) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST, ['message' => 'Роль не найдена',]);
        return ApiResponse::byResponseCode(ResponseCodes::getStatic()->SUCCESS, [
            'message' => 'Роль обновлена',
            'role' => $role
        ]);
    }
}
