<?php

namespace app\controllers\api\v1\manager;

use app\controllers\api\v1\ManagerController;
use app\models\User;
use yii\db\Query;
use Yii;

class UserController extends ManagerController
{

    protected $allowedRoles = ['client', 'buyer', 'manager', 'fulfillment'];


    public function behaviors()
    {


        $behaviors = parent::behaviors();
        return $behaviors;

    }

    public function actionIndex(string $role, int $limit = 10, $cursor = null, $query = null)
    {
        // Проверяем, что роль допустима
        if (!in_array($role, $this->allowedRoles)) {
            return $this->asJson([
                'success' => false,
                'message' => 'Invalid role'
            ]);
        }

        // Создаем базовый запрос
        $query_builder = User::find()
            ->where(['role' => $role]);

        // Добавляем поиск по фамилии, если задан параметр query
        if ($query !== null) {
            $query_builder->andWhere(['like', 'surname', $query]);
        }

        // Сортировка и курсор
        $query_builder->orderBy(['id' => SORT_ASC]);
        
        if ($cursor !== null) {
            $query_builder->andWhere(['>', 'id', $cursor]);
        }

        // Получаем пользователей с лимитом
        $users = $query_builder->limit($limit + 1)->all();

        // Проверяем, есть ли следующая страница
        $hasNextPage = count($users) > $limit;
        if ($hasNextPage) {
            array_pop($users); // Удаляем лишнего пользователя
        }

        // Получаем ID последнего пользователя для следующего курсора
        $nextCursor = $hasNextPage ? end($users)->id : null;

        // Формируем данные для ответа
        $responseData = [];
        foreach ($users as $user) {
            $responseData[] = [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'surname' => $user->surname,
                'name' => $user->name,
                'uuid' => $user->uuid,
                // Добавьте другие нужные поля
            ];
        }

        return $this->asJson([
            'success' => true,
            'data' => [
                'items' => $responseData,
                'next_cursor' => $nextCursor,
                'has_next_page' => $hasNextPage
            ]
        ]);
    }


    public function actionSearch(string $query)
    {
        // Создаем базовый запрос
        $users = User::find()
            ->select(['id', 'name', 'surname', 'uuid', 'role', 'email'])
            ->where(['or',
                ['like', 'surname', $query],
                ['like', 'name', $query],
                ['=', 'uuid', $query]
            ])
            ->limit(10)
            ->all();

        // Формируем данные для ответа
        $responseData = [];
        foreach ($users as $user) {
            $responseData[] = [
                'id' => $user->id,
                'name' => $user->name,
                'surname' => $user->surname,
                'uuid' => $user->uuid,
                'role' => $user->role,
                'email' => $user->email
            ];
        }

        return $this->asJson([
            'success' => true,
            'data' => [
                'items' => $responseData,
                'total_count' => count($responseData)
            ]
        ]);
    }


}



