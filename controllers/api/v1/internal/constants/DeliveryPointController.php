<?php

namespace app\controllers\api\v1\internal\constants;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\models\TypeDeliveryPoint;
use app\services\output\TypeDeliveryPointOutputService;
use Throwable;
use Yii;

class DeliveryPointController extends InternalController
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
     *     path="/api/v1/internal/constants/delivery-point",
     *     tags={"DeliveryPoint"},
     *     summary="Get list of delivery points",
     *     @OA\Response(response="200", description="Successful response")
     * )
     */
    public function actionIndex()
    {
        $typeDeliveryPoint = TypeDeliveryPoint::find();

        return ApiResponse::collection(
            TypeDeliveryPointOutputService::getCollection(
                $typeDeliveryPoint->column(),
            ),
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/internal/constants/delivery-point/create",
     *     tags={"DeliveryPoint"},
     *     summary="Create a new delivery point",
     *     @OA\Response(response="200", description="Delivery point created successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     *     @OA\Response(response="500", description="Internal server error")
     * )
     */
    public function actionCreate()
    {
        $apiCodes = TypeDeliveryPoint::apiCodes();
        $request = Yii::$app->request;
        $postParams = array_intersect_key(
            $request->post(),
            array_flip(['zh_name', 'ru_name', 'en_name']),
        );

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $typeDeliveryPoint = new TypeDeliveryPoint();
            $typeDeliveryPoint->load($postParams, '');
            if (!$typeDeliveryPoint->validate()) {
                return ApiResponse::codeErrors(
                    $apiCodes->NOT_VALID,
                    $typeDeliveryPoint->getFirstErrors(),
                );
            }
            if (!$typeDeliveryPoint->save()) {
                $transaction?->rollBack();
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $typeDeliveryPoint->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                TypeDeliveryPointOutputService::getEntity(
                    $typeDeliveryPoint->id,
                ),
            );
        } catch (Throwable $e) {
            $transaction?->rollBack();
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/internal/constants/delivery-point/update/{id}",
     *     tags={"DeliveryPoint"},
     *     summary="Update an existing delivery point",
     *     @OA\Parameter(name="id", in="path", required=true, description="Delivery Point ID", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Delivery point updated successfully"),
     *     @OA\Response(response="404", description="Delivery point not found"),
     *     @OA\Response(response="400", description="Validation error"),
     *     @OA\Response(response="500", description="Internal server error")
     * )
     */
    public function actionUpdate(int $id)
    {
        $apiCodes = TypeDeliveryPoint::apiCodes();
        $request = Yii::$app->request;
        $typeDeliveryPoint = TypeDeliveryPoint::findOne(['id' => $id]);
        $postParams = array_intersect_key(
            $request->post(),
            array_flip(['zh_name', 'ru_name', 'en_name']),
        );
        $transaction = null;

        if (!$typeDeliveryPoint) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        try {
            $transaction = Yii::$app->db->beginTransaction();

            $typeDeliveryPoint->load($postParams, '');

            if (!$typeDeliveryPoint->validate()) {
                return ApiResponse::codeErrors(
                    $apiCodes->NOT_VALID,
                    $typeDeliveryPoint->getFirstErrors(),
                );
            }

            if (!$typeDeliveryPoint->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $typeDeliveryPoint->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                TypeDeliveryPointOutputService::getEntity(
                    $typeDeliveryPoint->id,
                ),
            );
        } catch (Throwable $e) {
            $transaction?->rollBack();
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/internal/constants/delivery-point/delete/{id}",
     *     tags={"DeliveryPoint"},
     *     summary="Delete a delivery point",
     *     @OA\Parameter(name="id", in="path", required=true, description="Delivery Point ID", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Delivery point deleted successfully"),
     *     @OA\Response(response="404", description="Delivery point not found"),
     *     @OA\Response(response="500", description="Internal server error")
     * )
     */
    public function actionDelete(int $id)
    {
        $apiCodes = TypeDeliveryPoint::apiCodes();
        $typeDeliveryPoint = TypeDeliveryPoint::findOne(['id' => $id]);

        if (!$typeDeliveryPoint) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            if (!$typeDeliveryPoint->delete()) {
                $transaction?->rollBack();
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_DELETE,
                    $typeDeliveryPoint->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                TypeDeliveryPointOutputService::getEntity(
                    $typeDeliveryPoint->id,
                ),
            );
        } catch (Throwable $e) {
            $transaction?->rollBack();
            return ApiResponse::internalError($e);
        }
    }
}
