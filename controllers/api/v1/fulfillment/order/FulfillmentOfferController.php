<?php

namespace app\controllers\api\v1\fulfillment\order;

use app\components\ApiResponse;
use app\controllers\api\v1\FulfillmentController;
use app\helpers\POSTHelper;
use app\models\FulfillmentOffer;
use app\models\Order;
use app\models\User;
use app\services\output\FulfillmentOfferOutputService;
use app\services\UserActionLogService as Log;
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

    /**
     * @OA\Post(
     *     path="/api/v1/fulfillment/order/offer/create",
     *     summary="Создать предложение по выполнению",
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Неверный запрос"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заказ не найден"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера"
     *     )
     * )
     */
    public function actionCreate()
    {
        $apiCodes = FulfillmentOffer::apiCodes();

        try {
            $user = User::getIdentity();
            $params = POSTHelper::getPostWithKeys(
                ['order_id', 'overall_price'],
                true,
            );
            $params['currency'] = $user->settings->currency;
            Log::info('params', json_encode($params));

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
                'currency' => $params['currency'],
            ]);

            Log::info('fulfillmentOffer(CREATE)', json_encode($fulfillmentOffer));

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

    /**
     * @OA\Put(
     *     path="/api/v1/fulfillment/order/offer/update/{id}",
     *     summary="Обновить предложение по выполнению",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID предложения",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Предложение не найдено"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера"
     *     )
     * )
     */
    public function actionUpdate(int $id)
    {
        $apiCodes = FulfillmentOffer::apiCodes();

        try {
            $user = User::getIdentity();
            $fulfillmentOffer = FulfillmentOffer::findOne(['id' => $id]);
            $params = POSTHelper::getPostWithKeys(['overall_price']);
            $params['currency'] = $user->settings->currency;

            Log::info('params', json_encode($params));

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

            Log::info('fulfillmentOffer(UPDATE)', json_encode($fulfillmentOffer));

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
