<?php

namespace app\services\rolePermissions;

use Yii;
use Throwable;
use app\models\RoleModel;
use app\models\PermissionModel;

class RolePermissionService
{
    /**
     * Получить все разрешения роли
     * @param int $roleId ID роли
     * @return array
     */
    public static function getRolePermissions($roleId)
    {
        $role = RoleModel::findOne($roleId);
        if (!$role) {
            return ['error' => 'Роль не найдена'];
        }
        return $role->permissions;
    }

    /**
     * Добавить разрешение к роли
     * @param int $roleId ID роли
     * @param int $permissionId ID разрешения
     * @return array
     */
    public static function addPermissionToRole($roleId, $permissionId)
    {
        $role = RoleModel::findOne($roleId);
        if (!$role) {
            return ['error' => 'Роль не найдена'];
        }

        $permission = PermissionModel::findOne($permissionId);
        if (!$permission) {
            return ['error' => 'Разрешение не найдено'];
        }

        try {
            $role->link('permissions', $permission);
            return [
                'success' => 'Разрешение успешно добавлено к роли',
                'permissions' => $role->permissions
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Удалить разрешение из роли
     * @param int $roleId ID роли
     * @param int $permissionId ID разрешения
     * @return array
     */
    public static function removePermissionFromRole($roleId, $permissionId)
    {
        $role = RoleModel::findOne($roleId);
        if (!$role) {
            return ['error' => 'Роль не найдена'];
        }

        $permission = PermissionModel::findOne($permissionId);
        if (!$permission) {
            return ['error' => 'Разрешение не найдено'];
        }

        try {
            $role->unlink('permissions', $permission, true);
            return [
                'success' => 'Разрешение успешно удалено из роли',
                'permissions' => $role->permissions
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Установить разрешения для роли
     * @param int $roleId ID роли
     * @param array $permissionIds Массив ID разрешений
     * @return array
     */
    public static function setRolePermissions($roleId, array $permissionIds)
    {
        $role = RoleModel::findOne($roleId);
        if (!$role) {
            return ['error' => 'Роль не найдена'];
        }

        try {
            // Удаляем все текущие разрешения
            $role->unlinkAll('permissions', true);

            // Добавляем новые разрешения
            foreach ($permissionIds as $permissionId) {
                $permission = PermissionModel::findOne($permissionId);
                if ($permission) {
                    $role->link('permissions', $permission);
                }
            }

            return [
                'success' => 'Разрешения роли успешно обновлены',
                'permissions' => $role->permissions
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
