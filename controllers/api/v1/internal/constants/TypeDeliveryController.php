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
