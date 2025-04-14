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
        $behaviours['verbFilter']['actions']['update-markup'] = ['put'];
        return $behaviours;
    }
    /**
     * @param string $role
     * @param int $limit
     * @param int $page
     * @param string $sort
     * @param string $order
     * @param string $query
     * @return ApiResponse
     * 
     * 1. При сортировке name:
     * 1.1. Если role == client, то сортируем по имени, потом по фамилии, потом по id.
     * 1.2. Если role != client, то сортируем по названию организации, потом по id.
     * 2. При сортировке created_at сортируем по created_at, потом по id.
     * 3. При сортировке  markup сортируем по наценке, потом по id.
     * 4. Применять везде order, в зависимости от входного asc/desc.
     * 5. Применять query, если он есть, то (непустая строка)
     * 5.1. Если role == client, то ищем по вхождению по имени, потом по фамилии.
     * 5.2. Если role != client, то ищем по вхождению по названию организации.
     * 
     */
    public function actionIndex(
        string $role,
        int $limit = 10,
        int $page = 1,
        string $sort,
        string $order = 'asc',
        string $query = ''
    ) {
        if (!in_array($role, $this->allowedRoles)) {
            return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST);
        }

        try {
            $offset = ($page - 1) * $limit;
            $params = [':is_deleted' => false];

            // Базовый SQL запрос
            $sql = "SELECT * FROM user WHERE is_deleted = :is_deleted";

            // Добавляем поиск по query если он есть
            if (!empty($query)) {
                if ($role == 'client') {
                    $sql .= " AND (name LIKE :query OR surname LIKE :query)";
                    $params[':query'] = "%{$query}%";
                } else {
                    $sql .= " AND organization_name LIKE :query";
                    $params[':query'] = "%{$query}%";
                }
            }

            // Добавляем сортировку
            $sql .= " ORDER BY ";
            if ($sort == 'name') {
                if ($role == 'client') {
                    $sql .= "name " . ($order == 'asc' ? 'ASC' : 'DESC') . ", 
                            surname " . ($order == 'asc' ? 'ASC' : 'DESC') . ", 
                            id " . ($order == 'asc' ? 'ASC' : 'DESC');
                } else {
                    $sql .= "organization_name " . ($order == 'asc' ? 'ASC' : 'DESC') . ", 
                            id " . ($order == 'asc' ? 'ASC' : 'DESC');
                }
            } elseif ($sort == 'created_at') {
                $sql .= "created_at " . ($order == 'asc' ? 'ASC' : 'DESC') . ", 
                        id " . ($order == 'asc' ? 'ASC' : 'DESC');
            } elseif ($sort == 'markup') {
                $sql .= "markup " . ($order == 'asc' ? 'ASC' : 'DESC') . ", 
                        id " . ($order == 'asc' ? 'ASC' : 'DESC');
            }

            // Добавляем пагинацию
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;

            // Выполняем запрос для получения данных
            $users = Yii::$app->db->createCommand($sql)
                ->bindValues($params)
                ->queryAll();

            // Считаем общее количество записей
            $countSql = "SELECT COUNT(*) as count FROM user WHERE is_deleted = :is_deleted";
            if (!empty($query)) {
                if ($role == 'client') {
                    $countSql .= " AND (name LIKE :query OR surname LIKE :query)";
                } else {
                    $countSql .= " AND organization_name LIKE :query";
                }
            }

            $totalUsers = Yii::$app->db->createCommand($countSql)
                ->bindValues(array_filter($params, function ($key) {
                    return $key !== ':limit' && $key !== ':offset';
                }, ARRAY_FILTER_USE_KEY))
                ->queryScalar();

            $pages = ceil($totalUsers / $limit);
            if ($page > $pages) {
                return ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_FOUND);
            }

            $formattedUsers = [];
            foreach ($users as $user) {
                $userData = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'surname' => $user['surname'],
                    'organization_name' => $user['organization_name'] ?? null,
                    'uuid' => $user['uuid'],
                    'role' => $user['role'],
                    'email' => $user['email'],
                    'markup' => $user['markup'],
                    'created_at' => $user['created_at'],
                    'phone' => $user['phone_number'],
                    'telegram' => $user['telegram'] ?? null,
                ];

                // // Получаем аватар для пользователя, если есть
                // if (isset($user['id'])) {
                //     $avatarSql = "SELECT path FROM file WHERE entity_type = 'user' AND entity_id = :user_id LIMIT 1";
                //     $avatar = Yii::$app->db->createCommand($avatarSql)
                //         ->bindValue(':user_id', $user['id'])
                //         ->queryOne();

                //     if ($avatar) {
                //         $userData['avatar'] = $_ENV['APP_URL'] . $avatar['path'];
                //     }
                // }

                $formattedUsers[] = $userData;
            }

            return ApiResponse::code(
                $this->responseCodes->SUCCESS,
                [
                    'items' => $formattedUsers,
                    'total_count' => $totalUsers,
                    'total_pages' => $pages,
                    'current_page' => $page,
                    'limit' => $limit
                ]
            );
        } catch (\Exception $e) {
            return ApiResponse::byResponseCode(ResponseCodes::getStatic()->INTERNAL_ERROR, [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function actionView(int $id)
    {
        $user = User::findOne($id);
        if (!$user) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_FOUND);
        return ApiResponse::code($this->responseCodes->SUCCESS, [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'surname' => $user->surname,
                'organization_name' => $user->organization_name,
                'uuid' => $user->uuid,
                'role' => $user->role,
                'email' => $user->email,
                'markup' => $user->markup,
                'created_at' => $user->created_at,
                'phone' => $user->phone_number,
                'telegram' => $user->telegram,
                'avatar' => $user->avatar ? $user->avatar->path : null,
            ]
        ]);
    }

    public function actionUpdateMarkup()
    {
        $user_id = Yii::$app->request->post('user_id');
        $markup = Yii::$app->request->post('markup');

        $user = User::findOne($user_id);
        if (!$user) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_FOUND);

        $user->markup = $markup;

        $user->save();

        return ApiResponse::code($this->responseCodes->SUCCESS, [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'surname' => $user->surname,
                'organization_name' => $user->organization_name,
                'uuid' => $user->uuid,
                'role' => $user->role,
                'email' => $user->email,
                'markup' => $user->markup,
                'created_at' => $user->created_at,
                'phone' => $user->phone_number,
                'telegram' => $user->telegram,
                'avatar' => $user->avatar ? $user->avatar->path : null,
            ]
        ]);
    }
}
