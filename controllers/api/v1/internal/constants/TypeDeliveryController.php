<?php

namespace app\controllers\api\v1\internal\constants;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\models\TypeDelivery;
use app\services\output\TypeDeliveryOutputService;
use app\services\price\OrderDeliveryPriceService;
use app\services\SaveModelService;
use Throwable;
use Yii;

class TypeDeliveryController extends InternalController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];
        $behaviors['verbFilter']['actions']['create'] = ['post'];
        $behaviors['verbFilter']['actions']['update'] = ['put'];

        return $behaviors;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/internal/constants/type-delivery",
     *     summary="Получение списка типов доставки",
     *     tags={"Constants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="zh_name", type="string"),
     *                 @OA\Property(property="ru_name", type="string"),
     *                 @OA\Property(property="en_name", type="string"),
     *                 @OA\Property(property="available_for_all", type="boolean")
     *             )
     *         )
     *     )
     * )
     */
    public function actionIndex()
    {
        try {
            return ApiResponse::collection(
                TypeDeliveryOutputService::getCollection(
                    TypeDelivery::find()->column(),
                ),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/internal/constants/type-delivery/create",
     *     tags={"Type Delivery"},
     *     summary="Создать новый тип доставки",
     *     @OA\Response(response="200", description="Тип доставки успешно создан"),
     *     @OA\Response(response="400", description="Ошибка валидации"),
     *     @OA\Response(response="500", description="Внутренняя ошибка сервера")
     * )
     */
    public function actionCreate()
    {
        try {
            $apiCodes = TypeDelivery::apiCodes();
            $transaction = Yii::$app->db->beginTransaction();
            $typeDeliverySave = SaveModelService::loadValidateAndSave(
                new TypeDelivery(),
                ['en_name', 'ru_name', 'zh_name', 'available_for_all'],
                $transaction,
            );

            if (!$typeDeliverySave->success) {
                return $typeDeliverySave->apiResponse;
            }

            $typeDeliveryPricesSave = OrderDeliveryPriceService::addPriceRangeToTypeDelivery(
                $typeDeliverySave->model->id,
            );

            if (!$typeDeliveryPricesSave->success) {
                $transaction?->rollBack();

                return ApiResponse::code($apiCodes->ERROR_SAVE, [
                    'type_delivery_prices' =>
                    'Failed to save price range for type delivery',
                ]);
            }

            $transaction?->commit();

            return ApiResponse::info(
                TypeDeliveryOutputService::getEntity(
                    $typeDeliverySave->model->id,
                ),
            );
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/internal/constants/type-delivery/update/{id}",
     *     tags={"Type Delivery"},
     *     summary="Обновить существующий тип доставки",
     *     @OA\Parameter(name="id", in="path", required=true, description="ID типа доставки", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Тип доставки успешно обновлен"),
     *     @OA\Response(response="404", description="Тип доставки не найден"),
     *     @OA\Response(response="400", description="Ошибка валидации"),
     *     @OA\Response(response="500", description="Внутренняя ошибка сервера")
     * )
     */
    public function actionUpdate(int $id)
    {
        try {
            $apiCodes = TypeDelivery::apiCodes();
            $typeDelivery = TypeDelivery::findOne(['id' => $id]);

            if (!$typeDelivery) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            $typeDeliverySave = SaveModelService::loadValidateAndSave(
                $typeDelivery,
            );

            if (!$typeDeliverySave->success) {
                return $typeDeliverySave->apiResponse;
            }

            return ApiResponse::info(TypeDeliveryOutputService::getEntity($id));
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }
}
