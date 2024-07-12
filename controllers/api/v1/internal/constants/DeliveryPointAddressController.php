<?php

namespace app\controllers\api\v1\internal\constants;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\models\DeliveryPointAddress;
use app\models\TypeDeliveryPoint;
use app\services\output\DeliveryPointAddressOutputService;
use app\services\SaveModelService;

class DeliveryPointAddressController extends InternalController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];
        $behaviors['verbFilter']['actions']['create'] = ['post'];
        $behaviors['verbFilter']['actions']['update'] = ['put'];

        return $behaviors;
    }

    public function actionIndex()
    {
        $typeDeliveryPoint = DeliveryPointAddress::find();

        return ApiResponse::collection(
            DeliveryPointAddressOutputService::getCollection(
                $typeDeliveryPoint->column(),
            ),
        );
    }

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
