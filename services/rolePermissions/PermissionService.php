<?php

namespace app\services\rolePermissions;

use Yii;
use Throwable;
use app\models\User;
use app\models\PermissionModel;

class PermissionService
{
    public function __construct()
    {
        //
    }

    /**
     * Проверить наличие разрешения у пользователя
     * @param string $permission Название разрешения
     * @return bool
     */
    public static function checkPermission($permission)
    {
        $accessToken = Yii::$app->request->headers->get('Authorization');
        $accessToken = str_replace('Bearer ', '', $accessToken);
        $user = User::findOne(['access_token' => $accessToken]);

        if (!$user) {
            return false;
        }

        $role = $user->getRole();
        if (!$role) {
            return false;
        }

        foreach ($role->permissions as $perm) {
            if ($perm->name === $permission) {
                return true;
            }
        }
        return false;
    }

    /**
     * Получить все разрешения текущего пользователя
     * @return array
     */
    public static function getUserPermissions()
    {
        $accessToken = Yii::$app->request->headers->get('Authorization');
        $accessToken = str_replace('Bearer ', '', $accessToken);
        $user = User::findOne(['access_token' => $accessToken]);

        if (!$user) {
            return [];
        }

        $role = $user->getRole();
        return $role ? $role->permissions : [];
    }

    /**
     * Получить разрешение по ID
     * @param int $id ID разрешения
     * @return PermissionModel|null
     */
    public static function getPermission($id)
    {
        return PermissionModel::findOne($id);
    }

    /**
     * Получить все разрешения
     * @return PermissionModel[]
     */
    public static function getAllPermissions()
    {
        return PermissionModel::find()->all();
    }

    /**
     * Создать новое разрешение
     * @param string $name Название разрешения
     * @param string $description Описание разрешения
     * @return array|PermissionModel
     */
    public static function createPermission($name, $description)
    {
        $permission = new PermissionModel();
        $permission->name = $name;
        $permission->description = $description;
        if (!$permission->save()) {
            return $permission->getErrors();
        }
        return $permission;
    }

    /**
     * Обновить разрешение
     * @param int $id ID разрешения
     * @param array $data Данные для обновления
     * @return array|PermissionModel
     */
    public static function updatePermission($id, $data)
    {
        $permission = PermissionModel::findOne($id);
        if (!$permission) {
            return ['error' => 'Разрешение не найдено'];
        }

        $permission->load($data, '');
        if (!$permission->save()) {
            return $permission->getErrors();
        }
        return $permission;
    }

    /**
     * Удалить разрешение
     * @param int $id ID разрешения
     * @return array
     */
    public static function deletePermission($id)
    {
        $permission = PermissionModel::findOne($id);
        if (!$permission) {
            return ['error' => 'Разрешение не найдено'];
        }
        if ($permission->delete()) {
            return ['success' => 'Разрешение успешно удалено'];
        }
        return ['error' => 'Ошибка при удалении разрешения'];
    }

    public function __destruct()
    {
        //
    }
}
