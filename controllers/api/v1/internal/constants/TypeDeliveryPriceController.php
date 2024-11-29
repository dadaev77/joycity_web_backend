<?php

namespace app\controllers\api\v1\internal\constants;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\models\TypeDeliveryPrice;
use app\services\output\TypeDeliveryPriceOutputService;
use app\services\SaveModelService;
use Throwable;

class TypeDeliveryPriceController extends InternalController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];
        $behaviors['verbFilter']['actions']['update'] = ['put'];

        return $behaviors;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/internal/constants/type-delivery-price",
     *     summary="Получить список цен типов доставки",
     *     tags={"Type Delivery Price"},
     *     security={{"Bearer":{}}},
     *     @OA\Response(response="200", description="Успешный ответ"),
     *     @OA\Response(response="500", description="Внутренняя ошибка сервера")
     * )
     */
    public function actionIndex(int $type_delivery_id)
    {
        try {
            return ApiResponse::collection(
                TypeDeliveryPriceOutputService::getCollection(
                    TypeDeliveryPrice::find()
                        ->where(['type_delivery_id' => $type_delivery_id])
                        ->column(),
                ),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/internal/constants/type-delivery-price/update/{id}",
     *     summary="Обновить цену типа доставки",
     *     tags={"Type Delivery Price"},
     *     security={{"Bearer":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID цены типа доставки для обновления", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Успешное обновление"),
     *     @OA\Response(response="404", description="Не найдено"),
     *     @OA\Response(response="500", description="Внутренняя ошибка сервера")
     * )
     */
    public function actionUpdate(int $id)
    {
        try {
            $apiCodes = TypeDeliveryPrice::apiCodes();
            $typeDeliveryPrice = TypeDeliveryPrice::findOne(['id' => $id]);

            if (!$typeDeliveryPrice) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            $typeDeliveryPriceSave = SaveModelService::loadValidateAndSave(
                $typeDeliveryPrice,
                ['price'],
            );

            if (!$typeDeliveryPriceSave->success) {
                return $typeDeliveryPriceSave->apiResponse;
            }

            return ApiResponse::info(
                TypeDeliveryPriceOutputService::getEntity(
                    $typeDeliveryPriceSave->model->id,
                ),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }
}
