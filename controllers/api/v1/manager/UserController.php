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

        if (!in_array($role, $this->allowedRoles)) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST);

        try {
            $sortQueries = [
                'name' => 'name',
                'surname' => 'surname',
                'markup' => 'markup',
                'created_at' => 'created_at',
                'organization_name' => 'organization_name'
            ];
            $query = User::find();
            $query->where(['is_deleted' => false]);

            if ($sort == 'name') {
                if ($role == 'client') {
                    $query->orderBy(['name' => $order == 'asc' ? SORT_ASC : SORT_DESC, 'surname' => $order == 'asc' ? SORT_ASC : SORT_DESC, 'id' => $order == 'asc' ? SORT_ASC : SORT_DESC]);
                } else {
                    $query->orderBy(['organization_name' => $order == 'asc' ? SORT_ASC : SORT_DESC, 'id' => $order == 'asc' ? SORT_ASC : SORT_DESC]);
                }
            }

            if ($sort == 'created_at') {
                $query->orderBy(['created_at' => $order == 'asc' ? SORT_ASC : SORT_DESC, 'id' => $order == 'asc' ? SORT_ASC : SORT_DESC]);
            }

            if ($sort && $sort == 'markup') {
                $query->orderBy(['markup' => $order == 'asc' ? SORT_ASC : SORT_DESC, 'id' => $order == 'asc' ? SORT_ASC : SORT_DESC]);
            }

            if (!empty($query)) {
                if ($role == 'client') {
                    $query->andWhere(['or', ['like', 'name', $query], ['like', 'surname', $query]]);
                } else {
                    $query->andWhere(['like', 'organization_name', $query]);
                }
            }

            $query->offset(($page - 1) * $limit)->limit($limit);
            $users = $query->all();
            $formattedUsers = [];
            foreach ($users as $user) {
                $userdd =  [
                    'id' => $user->id,
                    'name' => $user->name,
                    'surname' => $user->surname,
                    'organization_name' => $user->organization_name ?? null,
                    'uuid' => $user->uuid,
                    'role' => $user->role,
                    'email' => $user->email,
                    'markup' => $user->markup,
                    'created_at' => $user->created_at,
                    'phone' => $user->phone_number,
                    'telegram' => $user->telegram ?? null,
                ];
                if ($user->avatar) $userdd['avatar'] = $_ENV['APP_URL'] . $user->avatar->path;
                $formattedUsers[] = $userdd;
            }
            $totalUsers = $query->count();
            $pages = ceil($totalUsers / $limit);
            if ($page > $pages) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_FOUND);
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
