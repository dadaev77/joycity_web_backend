<?php

namespace app\controllers\api\v1\buyer\order;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\controllers\api\v1\BuyerController;
use app\helpers\POSTHelper;
use app\models\BuyerOffer;
use app\models\Order;
use app\models\User;
use app\services\order\OrderDistributionService;
use app\services\order\OrderStatusService;
use app\services\output\BuyerOfferOutputService;
use app\services\RateService;
use Throwable;
use Yii;
use app\services\UserActionLogService as LogService;

class BuyerOfferController extends BuyerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['create'] = ['post'];
        $behaviors['verbFilter']['actions']['update'] = ['put'];
        $behaviors['verbFilter']['actions']['delete'] = ['delete'];
        array_unshift($behaviors['access']['rules'], [
            'actions' => ['create', 'update', 'delete'],
            'allow' => false,
            'matchCallback' => fn() => User::getIdentity()->role === User::ROLE_BUYER_DEMO,
        ]);
        $behaviors['access']['denyCallback'] = static function () {
            $response =
                User::getIdentity()->role === User::ROLE_BUYER_DEMO ?
                ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_AUTHENTICATED) :
                false;
            Yii::$app->response->data = $response;
        };
        return $behaviors;
    }

    public function actionCreate()
    {
        $apiCodes = BuyerOffer::apiCodes();

        try {
            $user = User::getIdentity();
            $params = POSTHelper::getPostWithKeys(
                [
                    'order_id',
                    'price_product',
                    'price_inspection',
                    'total_quantity',
                    'product_height',
                    'product_width',
                    'product_depth',
                    'product_weight',
                ],
                true,
            );
            $notValidParams = POSTHelper::getEmptyParams($params, true);

            if ($notValidParams) {
                $errors = array_map(
                    static fn($idx) => "Param `$notValidParams[$idx]` is empty",
                    array_flip($notValidParams),
                );

                return ApiResponse::codeErrors($apiCodes->BAD_REQUEST, $errors);
            }

            $order = Order::findOne(['id' => $params['order_id']]);

            if (!$order) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            $hasActiveBuyerOffer = (bool) array_filter(
                $order->buyerOffers,
                static fn($model) => in_array(
                    $model->status,
                    [BuyerOffer::STATUS_WAITING, BuyerOffer::STATUS_APPROVED],
                    true,
                ),
            );

            if ($hasActiveBuyerOffer) {
                return ApiResponse::code(
                    $apiCodes->DUPLICATE_ENTRY_BUYER_OFFER,
                );
            }

            if (
                $order->buyer_id !== $user->id //||
                // $order->status !== Order::STATUS_BUYER_ASSIGNED
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $transaction = Yii::$app->db->beginTransaction();
            $buyerOffer = new BuyerOffer([
                'created_at' => date('Y-m-d H:i:s'),
                'order_id' => $order->id,
                'buyer_id' => $user->id,
                'status' => BuyerOffer::STATUS_WAITING,
                'price_product' => RateService::putInUserCurrency(
                    $params['price_product'],
                ),
                'price_inspection' => RateService::putInUserCurrency(
                    $params['price_inspection'],
                ),
                'total_quantity' => $params['total_quantity'],
                'product_height' => $params['product_height'],
                'product_width' => $params['product_width'],
                'product_depth' => $params['product_depth'],
                'product_weight' => $params['product_weight'],
                'currency' => $user->settings->currency,
            ]);

            if (!$buyerOffer->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $buyerOffer->getFirstErrors(),
                );
            }

            $orderStatusChange = OrderStatusService::buyerOfferCreated(
                $buyerOffer->order_id,
            );

            if (!$orderStatusChange->success) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $orderStatusChange->reason,
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                BuyerOfferOutputService::getEntity($buyerOffer->id),
            );
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }

    public function actionUpdate(int $id)
    {
        $apiCodes = BuyerOffer::apiCodes();

        try {
            // Недавно пришёл апдейт что нельзя редактировать офер

            return ApiResponse::code($apiCodes->NO_ACCESS);

            $user = User::getIdentity();
            $buyerOffer = BuyerOffer::findOne(['id' => $id]);
            $params = POSTHelper::getPostWithKeys([
                'price_product',
                'price_inspection',
                'total_quantity',
            ]);

            if (!$buyerOffer) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (
                $buyerOffer->buyer_id !== $user->id ||
                !in_array(
                    $buyerOffer->order->status,
                    [
                        Order::STATUS_BUYER_OFFER_CREATED,
                        Order::STATUS_BUYER_OFFER_ACCEPTED,
                    ],
                    true,
                )
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $priceConverted = array_map(
                static fn($amount) => RateService::putInUserCurrency(
                    $amount,
                    $buyerOffer->order_id,
                ),
                $params,
            );

            $buyerOffer->load($priceConverted, '');

            if (!$buyerOffer->save()) {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $buyerOffer->getFirstErrors(),
                );
            }

            return ApiResponse::info(
                BuyerOfferOutputService::getEntity($buyerOffer->id),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }

    public function actionDelete(int $id)
    {
        $apiCodes = BuyerOffer::apiCodes();

        try {
            $user = User::getIdentity();
            $buyerOffer = BuyerOffer::findOne(['id' => $id]);

            if (!$buyerOffer) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (
                $buyerOffer->buyer_id !== $user->id ||
                !in_array(
                    $buyerOffer->order->status,
                    [
                        Order::STATUS_BUYER_OFFER_CREATED,
                        Order::STATUS_BUYER_OFFER_ACCEPTED,
                    ],
                    true,
                )
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $transaction = Yii::$app->db->beginTransaction();
            $buyerOffer->status = BuyerOffer::STATUS_DECLINED;

            if (!$buyerOffer->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $buyerOffer->getFirstErrors(),
                );
            }

            $order = $buyerOffer->order;
            $order->buyer_id = null;

            if (!$order->save()) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $order->getFirstErrors(),
                );
            }

            $orderStatus = OrderStatusService::created($order->id);

            if (!$orderStatus->success) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $orderStatus->reason,
                );
            }

            $reloadStatus = OrderDistributionService::reloadDistributionTask(
                $buyerOffer->order_id,
            );

            if (!$reloadStatus->success) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $reloadStatus->reason,
                );
            }

            $transaction?->commit();

            return ApiResponse::code($apiCodes->SUCCESS);
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }
}
