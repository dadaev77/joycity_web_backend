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
        $behaviors['verbFilter']['actions']['delete'] = ['delete'];

        return $behaviors;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/internal/constants/delivery-point-address",
     *     tags={"DeliveryPointAddress"},
     *     summary="Get list of delivery point addresses",
     *     @OA\Response(response="200", description="Successful response")
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
     *     tags={"DeliveryPointAddress"},
     *     summary="Create a new delivery point address",
     *     @OA\Response(response="200", description="Delivery point address created successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     *     @OA\Response(response="500", description="Internal server error")
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
     *     tags={"DeliveryPointAddress"},
     *     summary="Update an existing delivery point address",
     *     @OA\Parameter(name="id", in="path", required=true, description="Delivery Point Address ID", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Delivery point address updated successfully"),
     *     @OA\Response(response="404", description="Delivery point address not found"),
     *     @OA\Response(response="400", description="Validation error"),
     *     @OA\Response(response="500", description="Internal server error")
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
     *     tags={"DeliveryPointAddress"},
     *     summary="Delete a delivery point address",
     *     @OA\Parameter(name="id", in="path", required=true, description="Delivery Point Address ID", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Delivery point address deleted successfully"),
     *     @OA\Response(response="404", description="Delivery point address not found"),
     *     @OA\Response(response="500", description="Internal server error")
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
