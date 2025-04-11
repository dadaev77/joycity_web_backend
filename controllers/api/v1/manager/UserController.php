<?php

namespace app\controllers\api\v1\manager;

use app\controllers\api\v1\ManagerController;
use app\models\User;
use app\components\response\ResponseCodes;
use app\components\ApiResponse;
use Yii;

class UserController extends ManagerController
{
    protected $allowedRoles = ['client', 'buyer', 'manager', 'fulfillment', 'unused'];

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
        string $query = ''
    ) {

        if (!in_array($role, $this->allowedRoles)) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST);
        try {

            $sortQueries = [
                'name' => 'name',
                'surname' => 'surname',
                'markup' => 'markup',
                'created_at' => 'created_at'
            ];

            $query = User::find();
            $query->where(['is_deleted' => false]);
            if ($sort) {
                $query->orderBy([$sortQueries[$sort] => $order]);
            }



            return $query->all();


            // return ApiResponse::code(
            //     $this->responseCodes->SUCCESS,
            //     [
            //         'items' => $formattedUsers,
            //         'total_count' => $totalUsers,
            //         'total_pages' => $pages,
            //         'current_page' => $page,
            //         'limit' => $limit
            //     ]
            // );
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
