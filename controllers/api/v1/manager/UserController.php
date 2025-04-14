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
     *         description="Поле для сортировки (name,surname, markup или created_at)",
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
    public function actionIndex(
        string $role,
        int $limit = 10,
        int $page = 1,
        string $sort,
        string $order = 'asc',
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
            $query->andWhere(['role' => $role]);

            if ($sort) {
                $query->orderBy([$sortQueries[$sort] => $order == 'asc' ? SORT_ASC : SORT_DESC]);
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
                    'telegarm' => $user->telegram ?? null,
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
                'avatar' => $user->avatar ? $_ENV['APP_URL'] . $user->avatar->path : null,
            ]
        ]);
    }
}
