<?php

namespace app\console\controllers;

use yii\console\Controller;
use Yii;
use app\models\RoleModel;

class RoleController extends Controller
{
    public function actionIndex()
    {
        $this->stdout("Команда для миграции ролей из старой реализации\n");
        $this->stdout("на новую с использованием моделей:\n");
        $this->stdout(" - RoleModel \n");
        $this->stdout(" - PermissionModel \n\n");
        $this->stdout("Для продолжения введите 'yes'\n");

        $choice = $this->prompt("Введите 'yes' для продолжения: ");
        if ($choice === 'yes') {
            $this->stdout("Выполняется миграция ролей...\n");
            $this->actionMigrate();
        } else {
            $this->stdout("Выход...\n");
        }
    }

    public function actionMigrate()
    {
        Yii::$app->runAction('migrate/up', ['interactive' => 0]);
        $this->addRoles();
        $this->assignRolesToUsers();
        $this->stdout("Миграция завершена!\n");
        die();
    }

    private function addRoles()
    {
        $this->stdout("Добавление ролей...\n");
        $roles = [
            [
                'name' => 'admin',
                'description' => 'Администратор',
            ],
            [
                'name' => 'super-admin',
                'description' => 'Супер-администратор',
            ],
            [
                'name' => 'client',
                'description' => 'Клиент',
            ],
            [
                'name' => 'client-demo',
                'description' => 'Клиент-демо',
            ],
            [
                'name' => 'manager',
                'description' => 'Менеджер',
            ],
            [
                'name' => 'buyer',
                'description' => 'Покупатель',
            ],
            [
                'name' => 'buyer-demo',
                'description' => 'Покупатель-демо',
            ],
            [
                'name' => 'fulfillment',
                'description' => 'Фулфилмент',
            ],
        ];

        try {
            $added = 0;
            foreach ($roles as $roleT) {
                $isset = \app\models\RoleModel::findOne(['name' => $roleT['name']]);
                if (!$isset) {
                    $role = new \app\models\RoleModel();
                    $role->name = $roleT['name'];
                    $role->description = $roleT['description'];
                    if (!$role->save()) {
                        return ['status' => 'error', 'message' => $role->getErrors()];
                    }
                    $added++;
                }
            }
            if ($added > 0) {
                return ['status' => 'ok', 'message' => 'Роли успешно добавлены', 'added' => $added];
            } else {
                return ['status' => 'ok', 'message' => 'Роли уже существуют'];
            }
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
        $this->stdout("Роли добавлены!\n");
    }

    private function assignRolesToUsers()
    {
        $this->stdout("Назначение ролей пользователям...\n");
        \app\services\RoleService::migrateRoles();
        $this->stdout("Роли назначены!\n");
    }
}
