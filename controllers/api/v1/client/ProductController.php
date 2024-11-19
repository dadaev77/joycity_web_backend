<?php

namespace app\controllers\api\v1\client;

use app\components\ApiResponse;
use app\controllers\api\v1\ClientController;
use app\models\Product;
use app\services\output\ProductOutputService;

class ProductController extends ClientController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['view'] = ['get'];

        return $behaviors;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/client/product/view",
     *     summary="Получить информацию о продукте по ID",
     *     description="Возвращает информацию о продукте по указанному ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID продукта",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ с информацией о продукте",
     *         @OA\JsonContent(type="object", properties={
     *             @OA\Property(property="info", type="object")
     *         })
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Продукт не найден"
     *     )
     * )
     */
    public function actionView(int $id)
    {
        $apiCodes = Product::apiCodes();
        $isset = Product::isset(['id' => $id]);

        if (!$isset) {
            return ApiResponse::byResponseCode($apiCodes->NOT_FOUND);
        }

        return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
            'info' => ProductOutputService::getEntity(
                $id,
                'small'
            ),
        ]);
    }
}
