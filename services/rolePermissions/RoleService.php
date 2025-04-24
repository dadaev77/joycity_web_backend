<?php

namespace app\services\rolePermissions;

use Yii;
use Throwable;
use app\models\RoleModel;

class RoleService
{
    public function __construct()
    {
        //
    }

    /**
     * Получить роль по ID
     * @param int $id ID роли
     * @return RoleModel|null
     */
    public static function getRole($id)
    {
        return RoleModel::findOne($id);
    }

    /**
     * Получить все роли
     * @return RoleModel[]
     */
    public static function getAllRoles()
    {
        return RoleModel::find()->all();
    }

    /**
     * Создать новую роль
     * @param string $name Название роли
     * @param string $description Описание роли
     * @return array|RoleModel
     */
    public static function createRole($name, $description)
    {
        $role = new RoleModel();
        $role->name = $name;
        $role->description = $description;
        if (!$role->save()) {
            return $role->getErrors();
        }
        return $role;
    }

    /**
     * Обновить роль
     * @param int $id ID роли
     * @param array $data Данные для обновления
     * @return array|RoleModel
     */
    public static function updateRole($id, $data)
    {
        $role = RoleModel::findOne($id);
        if (!$role) {
            return ['error' => 'Роль не найдена'];
        }

        $role->name = $data['name'];
        $role->description = $data['description'];

        if (!$role->save()) {
            return $role->getErrors();
        }
        return $role;
    }

    /**
     * Удалить роль
     * @param int $id ID роли
     * @return array
     */
    public static function deleteRole($id)
    {
        $role = RoleModel::findOne($id);
        if (!$role) {
            return ['error' => 'Роль не найдена'];
        }
        if ($role->delete()) {
            return ['success' => 'Роль успешно удалена'];
        }
        return ['error' => 'Ошибка при удалении роли'];
    }

    /**
     * Миграция ролей пользователей
     * @return array
     */
    public static function migrateRoles()
    {
        $users = Yii::$app->db->createCommand('SELECT * FROM user')->queryAll();
        $roles = RoleModel::find()->all();

        $roles = array_reduce($roles, function ($carry, $role) {
            $carry[$role->name] = $role->id;
            return $carry;
        }, []);

        try {
            $added = 0;
            foreach ($users as $user) {
                $user['role_id'] = $roles[$user['role']];
                Yii::$app->db->createCommand('UPDATE user SET role_id = :role_id WHERE id = :id')
                    ->bindValue(':role_id', $user['role_id'])
                    ->bindValue(':id', $user['id'])
                    ->execute();
                $added++;
            }

            return [
                'status' => 'ok',
                'message' => $added > 0 ? 'Роли успешно добавлены' : 'Роли уже существуют',
                'added' => $added
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function __destruct()
    {
        //
    }
}
