<?php

namespace app\controllers\api\v1\internal\constants;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\models\DeliveryPointAddress;
use app\services\output\DeliveryPointAddressOutputService;
use app\services\SaveModelService;
use Throwable;

class DeliveryPointAddressController extends InternalController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];
        $behaviors['verbFilter']['actions']['create'] = ['post'];
        $behaviors['verbFilter']['actions']['update'] = ['put'];
        $behaviors['verbFilter']['actions']['delete'] = ['delete'];

        return $behaviors;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/internal/constants/delivery-point-address",
     *     tags={"Delivery Point Address"},
     *     summary="Получить список адресов пунктов доставки",
     *     @OA\Response(response="200", description="Успешный ответ")
     * )
     */
    public function actionIndex()
    {
        $typeDeliveryPoint = DeliveryPointAddress::find();

        return ApiResponse::collection(
            DeliveryPointAddressOutputService::getCollection(
                $typeDeliveryPoint->column(),
            ),
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/internal/constants/delivery-point-address",
     *     tags={"Delivery Point Address"},
     *     summary="Создать новый адрес пункта доставки",
     *     @OA\Response(response="200", description="Адрес пункта доставки успешно создан"),
     *     @OA\Response(response="400", description="Ошибка валидации"),
     *     @OA\Response(response="500", description="Внутренняя ошибка сервера")
     * )
     */
    public function actionCreate()
    {
        $deliveryPointAddress = new DeliveryPointAddress();
        $deliveryPointAddress->type_delivery_point_id =
            TypeDeliveryPoint::TYPE_WAREHOUSE;

        $deliveryPointAddressSave = SaveModelService::loadValidateAndSave(
            $deliveryPointAddress,
            ['address', 'user_id'],
            null,
            true,
        );

        if (!$deliveryPointAddressSave->success) {
            return $deliveryPointAddressSave->apiResponse;
        }
        return ApiResponse::info(
            DeliveryPointAddressOutputService::getEntity(
                $deliveryPointAddress->id,
            ),
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v1/internal/constants/delivery-point-address/{id}",
     *     tags={"Delivery Point Address"},
     *     summary="Обновить существующий адрес пункта доставки",
     *     @OA\Parameter(name="id", in="path", required=true, description="ID адреса пункта доставки", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Адрес пункта доставки успешно обновлен"),
     *     @OA\Response(response="404", description="Адрес пункта доставки не найден"),
     *     @OA\Response(response="400", description="Ошибка валидации"),
     *     @OA\Response(response="500", description="Внутренняя ошибка сервера")
     * )
     */
    public function actionUpdate(int $id)
    {
        $apiCodes = DeliveryPointAddress::apiCodes();
        $deliveryPointAddress = DeliveryPointAddress::findOne(['id' => $id]);

        if (!$deliveryPointAddress) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        $deliveryPointAddressSave = SaveModelService::loadValidateAndSave(
            $deliveryPointAddress,
            ['address'],
        );

        if (!$deliveryPointAddressSave->success) {
            return $deliveryPointAddressSave->apiResponse;
        }
        return ApiResponse::info(
            DeliveryPointAddressOutputService::getEntity(
                $deliveryPointAddress->id,
            ),
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/internal/constants/delivery-point-address/{id}",
     *     tags={"Delivery Point Address"},
     *     summary="Удалить адрес пункта доставки",
     *     @OA\Parameter(name="id", in="path", required=true, description="ID адреса пункта доставки", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Адрес пункта доставки успешно удален"),
     *     @OA\Response(response="404", description="Адрес пункта доставки не найден"),
     *     @OA\Response(response="500", description="Внутренняя ошибка сервера")
     * )
     */
    public function actionDelete(int $id)
    {
        $apiCodes = DeliveryPointAddress::apiCodes();
        $deliveryPointAddress = DeliveryPointAddress::findOne(['id' => $id]);

        if (!$deliveryPointAddress) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        $deliveryPointAddress->is_deleted = 1;

        if (!$deliveryPointAddress->save()) {
            return ApiResponse::codeErrors(
                $apiCodes->ERROR_SAVE,
                $deliveryPointAddress->getFirstErrors(),
            );
        }

        return ApiResponse::code($apiCodes->SUCCESS);
    }
}
