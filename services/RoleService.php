<?php

namespace app\services;

use Yii;
use Throwable;

class RoleService
{

    public function __construct()
    {
        //
    }

    public static function getRole($id)
    {
        return \app\models\RoleModel::findOne($id);
    }

    public static function getPermissions($id)
    {
        return \app\models\PermissionModel::findOne($id);
    }

    public static function createRole($name, $description)
    {
        $role = new \app\models\RoleModel();
        $role->name = $name;
        $role->description = $description;
        $role->save();
    }

    public static function createPermission($name, $description)
    {
        $permission = new \app\models\PermissionModel();
        $permission->name = $name;
        $permission->description = $description;
        $permission->save();
    }

    public static function linkPermissionToRole($roleId, $permissionId)
    {
        $role = \app\models\RoleModel::findOne($roleId);
        $permission = \app\models\PermissionModel::findOne($permissionId);
        $role->link('permissions', $permission);
    }

    public static function unlinkPermissionFromRole($roleId, $permissionId)
    {
        $role = \app\models\RoleModel::findOne($roleId);
        $permission = \app\models\PermissionModel::findOne($permissionId);
        $role->unlink('permissions', $permission);
    }

    public static function migrateRoles()
    {
        $users = \app\models\User::find()->all();
        $roles = \app\models\RoleModel::find()->all();

        $roles = array_reduce($roles, function ($carry, $role) {
            $carry[$role->name] = $role->id;
            return $carry;
        }, []);

        try {
            $added = 0;
            foreach ($users as $user) {
                $user->role_id = $roles[$user->role];
                $user->save();
                $added++;
            }
            if ($added > 0) {
                return ['status' => 'ok', 'message' => 'Роли успешно добавлены', 'added' => $added];
            } else {
                return ['status' => 'ok', 'message' => 'Роли уже существуют', 'added' => $added];
            }
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public static function pasteRoles() {}

    public function __destruct()
    {
        //
    }
}
