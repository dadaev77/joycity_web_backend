<?php

namespace app\controllers\api\v1\client;

use app\components\ApiResponse;
use app\controllers\api\v1\ClientController;
use app\models\User;
use app\services\output\BuyerOutputService;

class BuyerController extends ClientController
{
    public function behaviors()
    {
        $behaviours = parent::behaviors();
        $behaviours['verbFilter']['actions']['view'] = ['get'];

        return $behaviours;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/client/buyer/view/{id}",
     *     summary="Получить информацию о покупателе",
     *     description="Возвращает информацию о покупателе по его ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", description="ID покупателя"),
     *             @OA\Property(property="name", type="string", description="Имя покупателя"),
     *             @OA\Property(property="email", type="string", description="Email покупателя")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Покупатель не найден"
     *     )
     * )
     */
    public function actionView(int $id)
    {
        $apiCodes = User::apiCodes();
        $isset = User::isset(['id' => $id]);
        if (!$isset) {
            return ApiResponse::byResponseCode($apiCodes->NOT_FOUND, null, 404);
        }

        if (User::getIdentity()->is([User::ROLE_CLIENT_DEMO, User::ROLE_CLIENT])) {
            return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
                'info' => BuyerOutputService::getEntity($id),
            ]);
        }
    }
}
