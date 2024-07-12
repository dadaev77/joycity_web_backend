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

    public function actionIndex()
    {
        $typeDeliveryPoint = TypeDeliveryPoint::find();

        return ApiResponse::collection(
            TypeDeliveryPointOutputService::getCollection(
                $typeDeliveryPoint->column(),
            ),
        );
    }

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
}
