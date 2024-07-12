<?php

namespace app\controllers\api\v1\fulfillment\order;

use app\components\ApiResponse;
use app\controllers\api\v1\FulfillmentController;
use app\helpers\POSTHelper;
use app\models\FulfillmentOffer;
use app\models\Order;
use app\models\User;
use app\services\output\FulfillmentOfferOutputService;
use Throwable;
use Yii;

class FulfillmentOfferController extends FulfillmentController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['create'] = ['post'];
        $behaviors['verbFilter']['actions']['update'] = ['put'];
        return $behaviors;
    }

    public function actionCreate()
    {
        $apiCodes = FulfillmentOffer::apiCodes();

        try {
            $user = User::getIdentity();
            $params = POSTHelper::getPostWithKeys(
                ['order_id', 'overall_price'],
                true,
            );
            $notValidParams = POSTHelper::getEmptyParams($params, true);

            if ($notValidParams) {
                $errors = array_map(
                    static fn($idx) => "Param `$notValidParams[$idx]` empty",
                    array_flip($notValidParams),
                );

                return ApiResponse::codeErrors($apiCodes->BAD_REQUEST, $errors);
            }

            $order = Order::findOne(['id' => $params['order_id']]);

            if (!$order) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if ($order->fulfillment_id !== $user->id) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $existingOffer = FulfillmentOffer::find()
                ->where([
                    'order_id' => $order->id,
                    'fulfillment_id' => $user->id,
                ])
                ->exists();

            if ($existingOffer) {
                return ApiResponse::code(
                    $apiCodes->DUPLICATE_ENTRY_FULFILLMENT_OFFER,
                );
            }

            $transaction = Yii::$app->db->beginTransaction();

            $fulfillmentOffer = new FulfillmentOffer([
                'created_at' => date('Y-m-d H:i:s'),
                'order_id' => $order->id,
                'fulfillment_id' => $user->id,
                'status' => FulfillmentOffer::STATUS_CREATED,
                'overall_price' => $params['overall_price'],
            ]);

            if (!$fulfillmentOffer->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $fulfillmentOffer->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                FulfillmentOfferOutputService::getEntity($fulfillmentOffer->id),
            );
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }

    public function actionUpdate(int $id)
    {
        $apiCodes = FulfillmentOffer::apiCodes();

        try {
            $user = User::getIdentity();
            $fulfillmentOffer = FulfillmentOffer::findOne(['id' => $id]);
            $params = POSTHelper::getPostWithKeys(['overall_price']);

            if (!$fulfillmentOffer) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (
                $fulfillmentOffer->fulfillment_id !== $user->id ||
                !in_array(
                    $fulfillmentOffer->status,
                    (array) FulfillmentOffer::STATUS_CREATED,
                    true,
                )
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $fulfillmentOffer->load($params, '');

            if (!$fulfillmentOffer->save()) {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $fulfillmentOffer->getFirstErrors(),
                );
            }

            return ApiResponse::info(
                FulfillmentOfferOutputService::getEntity($fulfillmentOffer->id),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }
}
