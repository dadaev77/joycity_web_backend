<?php

namespace app\controllers\api\v1\buyer;

use app\components\ApiResponse;
use app\controllers\api\v1\BuyerController as BuyerControllerParent;
use app\models\User;
use app\services\output\BuyerOutputService;

class BuyerController extends BuyerControllerParent
{
    public function __construct()
    {
        //
    }

    public function behaviors()
    {
        $behaviours = parent::behaviors();
        $behaviours['verbFilter']['actions']['view'] = ['get'];
        $behaviours['verbFilter']['actions']['buyer'] = ['get'];

        return $behaviours;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/buyer/view/{id}",
     *     summary="Получение информации о покупателе",
     *     description="Этот метод возвращает информацию о покупателе по его ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Информация о покупателе",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Покупатель не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Покупатель не найден")
     *         )
     *     )
     * )
     */
    public function actionView(int $id)
    {
        $apiCodes = User::apiCodes();
        $isset = User::isset(['id' => $id]);

        if (!$isset) return ApiResponse::code($apiCodes->NOT_FOUND);

        $user = User::findOne($id);

        if ($user->is([User::ROLE_BUYER, User::ROLE_BUYER_DEMO])) {
            return ApiResponse::info(BuyerOutputService::getEntity($id));
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/buyer/buyer/{id}",
     *     summary="Получение информации о покупателе (другой метод)",
     *     description="Этот метод возвращает информацию о покупателе по его ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Информация о покупателе",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Покупатель не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Покупатель не найден")
     *         )
     *     )
     * )
     */
    public function actionBuyer(int $id)
    {
        $apiCodes = User::apiCodes();
        $isset = User::isset(['id' => $id]);
        if (!$isset) return ApiResponse::code($apiCodes->NOT_FOUND);

        $user = User::findOne($id);
        if ($user->is([User::ROLE_BUYER, User::ROLE_BUYER_DEMO])) {
            return ApiResponse::info(BuyerOutputService::getEntity($id));
        }
    }
}
