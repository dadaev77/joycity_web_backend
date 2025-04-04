<?php

namespace app\services;

use Yii;
use Throwable;

class PermissionControlService
{

    public function __construct()
    {
        //
    }

    /**
     * Check if a user has a permission
     * @param string $permission The name of the permission
     * @return bool True if the user has the permission, false otherwise
     */
    public static function checkPermission($permission)
    {
        $accessToken = Yii::$app->request->headers->get('Authorization');
        $accessToken = str_replace('Bearer ', '', $accessToken);
        $user = \app\models\User::findOne(['access_token' => $accessToken]);
        $role = $user->getRole();
        $permissions = $role->permissions;
        if ($permissions->name == $permission) {
            return true;
        }
        return false;
    }

    /**
     * Get the permissions of a role
     * @return array The permissions of the role
     */
    public static function getPermissions()
    {
        $accessToken = Yii::$app->request->headers->get('Authorization');
        $accessToken = str_replace('Bearer ', '', $accessToken);
        $user = \app\models\User::findOne(['access_token' => $accessToken]);
        $role = $user->getRole();
        $permissions = $role->permissions;
        return $permissions;
    }

    /**
     * Create a new role
     * @param string $name The name of the role
     * @param string $description The description of the role
     * @return array The created role
     */
    public static function createRole($name, $description)
    {
        $role = new \app\models\RoleModel();
        $role->name = $name;
        $role->description = $description;
        if (!$role->save()) {
            return $role->getErrors();
        }
        return $role;
    }

    /**
     * Create a new permission
     * @param string $name The name of the permission
     * @param string $description The description of the permission
     * @return array The created permission
     */
    public static function createPermission($name, $description)
    {
        $permission = new \app\models\PermissionModel();
        $permission->name = $name;
        $permission->description = $description;
        if (!$permission->save()) {
            return $permission->getErrors();
        }
        return $permission;
    }

    /**
     * Add a permission to a role
     * @param int $roleId The ID of the role
     * @param int $permissionId The ID of the permission
     * @return array The permissions of the role after addition
     */
    public static function addPermissionToRole($roleId, $permissionId)
    {
        $role = \app\models\RoleModel::findOne($roleId);
        $permission = \app\models\PermissionModel::findOne($permissionId);
        try {
            $role->link('permissions', $permission);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        return $role->permissions;
    }

    /**
     * Remove a permission from a role
     * @param int $roleId The ID of the role
     * @param int $permissionId The ID of the permission
     * @return array The permissions of the role after removal
     */
    public static function removePermissionFromRole($roleId, $permissionId)
    {
        $role = \app\models\RoleModel::findOne($roleId);
        $permission = \app\models\PermissionModel::findOne($permissionId);
        try {
            $role->unlink('permissions', $permission, true);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        return $role->permissions;
    }

    public function __destruct()
    {
        //
    }
}
