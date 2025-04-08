<?php

namespace app\controllers\api\v1\manager;

use app\controllers\api\v1\ManagerController;
use app\models\User;
use app\components\response\ResponseCodes;
use app\components\ApiResponse;
use Yii;

class UserController extends ManagerController
{
    protected $allowedRoles = ['client', 'buyer', 'manager', 'fulfillment'];

    protected $responseCodes;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->responseCodes = ResponseCodes::getStatic();
    }

    public function behaviors()
    {
        $behaviours = parent::behaviors();
        $behaviours['verbFilter']['actions']['index'] = ['get'];
        $behaviours['verbFilter']['actions']['search'] = ['get'];
        $behaviours['verbFilter']['actions']['update-markup'] = ['post'];
        return $behaviours;
    }
    /**
     * @OA\Get(
     *     path="/api/v1/manager/user",
     *     summary="Получить список пользователей с сортировкой",
     *     tags={"Manager - Users"},
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         required=true,
     *         description="Роль пользователя (client, buyer, manager, fulfillment)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Количество записей на странице",
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Номер страницы",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="ID пользователя для получения информации о конкретном пользователе",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Поле для сортировки (name,surname или markup)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Порядок сортировки (asc или desc)",
     *         @OA\Schema(type="string", default="asc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="items", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="total_count", type="integer"),
     *             @OA\Property(property="total_pages", type="integer"),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="limit", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Пользователи не найдены"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Неверная роль"
     *     )
     * )
     */
    public function actionIndex(string $role, int $limit = 10, int $page = 1, int $id = null)
    {
        if (!in_array($role, $this->allowedRoles)) {
            return ApiResponse::code(
                $this->responseCodes->BAD_REQUEST,
                ['message' => 'Invalid role.'],
                422
            );
        }
        
        $query = User::find()->where(['role' => $role, 'is_deleted' => 0]);
        $query->select(['id', 'name', 'surname', 'uuid', 'role', 'email', 'markup']);

        // Получаем параметры сортировки
        $sort = Yii::$app->request->get('sort');
        $order = Yii::$app->request->get('order', 'asc');
        
        // Применяем сортировку
        if ($sort) {
            if ($sort === 'markup') {
                $query->orderBy(['markup' => $order === 'desc' ? SORT_DESC : SORT_ASC]);
            } elseif ($sort === 'name,surname') {
                // Сначала сортируем по имени, затем по фамилии
                $direction = $order === 'desc' ? SORT_DESC : SORT_ASC;
                $query->orderBy([
                    'name' => $direction,
                    'surname' => SORT_ASC // Всегда сортируем фамилии по возрастанию для лучшей читаемости
                ]);
            }
        } else {
            $query->orderBy(['id' => SORT_DESC]); // Дефолтная сортировка
        }

        $totalUsers = $query->count(); 
        $pages = ceil($totalUsers / $limit);
        
        $users = $query
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->all();

        if ($id) {
            $user = User::findOne($id);
            if (!$user) {
                return ApiResponse::code($this->responseCodes->NOT_FOUND, ['message' => 'User not found.']);
            }
            return ApiResponse::code(
                $this->responseCodes->SUCCESS,
                ['info' => $user]
            );
        }

        if (count($users) < 1) {
            return ApiResponse::code(
                $this->responseCodes->NOT_FOUND,
                ['message' => 'No users found for the specified role.'],
                404
            );
        }

        return ApiResponse::code(
            $this->responseCodes->SUCCESS,
            [
                'items' => $users,
                'total_count' => $totalUsers,
                'total_pages' => $pages,
                'current_page' => $page,
                'limit' => $limit
            ]   
        );
    }


    public function actionSearch(
        string $query,
        int $limit = 10,
        int $page = 1
    )
    {
        $queryBuilder = User::find()
            ->select(['id', 'name', 'surname', 'uuid', 'role', 'email', 'markup'])
            ->where(['or',
                ['like', 'email', $query],
                ['like', 'surname', $query],
                ['like', 'name', $query],
                ['like', 'uuid', $query]
            ])
            ->andWhere(['is_deleted' => 0]);

        $totalUsers = $queryBuilder->count();
        $pages = ceil($totalUsers / $limit);

        $users = $queryBuilder
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->all();

        if ( count($users) < 1 ) return ApiResponse::code($this->responseCodes->NOT_FOUND,['message' => 'No users found for the specified query.'],404);

        return ApiResponse::code(
            $this->responseCodes->SUCCESS,
            [
                'items' => $users,
                'total_count' => $totalUsers,
                'total_pages' => $pages,
                'current_page' => $page,
                'limit' => $limit
            ]
        );
    }

}



