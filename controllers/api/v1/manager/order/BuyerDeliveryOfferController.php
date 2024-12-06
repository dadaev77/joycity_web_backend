<?php

namespace app\controllers\api\v1\manager\order;

use app\components\ApiResponse;
use app\controllers\api\v1\ManagerController;
use app\helpers\POSTHelper;
use app\models\BuyerDeliveryOffer;
use app\models\Order;
use app\models\OrderRate;
use app\models\Rate;
use app\models\User;
use app\models\Waybill;
use app\services\output\BuyerDeliveryOfferOutputService;
use app\services\RateService;
use app\services\WaybillService;
use Mpdf\Mpdf;
use Throwable;
use Yii;
use app\services\UserActionLogService as Log;

class BuyerDeliveryOfferController extends ManagerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['create'] = ['post'];
        $behaviors['verbFilter']['actions']['paid'] = ['put'];

        return $behaviors;
    }

    public function actionCreate()
    {
        $apiCodes = Order::apiCodes();

        try {
            $user = User::getIdentity();
            $params = POSTHelper::getPostWithKeys(
                [
                    'order_id',
                    'price_product',
                    'total_quantity',
                    'product_height',
                    'product_width',
                    'product_depth',
                    'product_weight',
                    'package_expenses',
                    'amount_of_space',
                    'type_delivery_id',
                    'type_delivery_point_id',
                    'cargo_number'
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
                return ApiResponse::codeErrors($apiCodes->BAD_REQUEST, [
                    'order_id' => 'Order is not found',
                ]);
            }

            if (
                $order->manager_id !== $user->id ||
                $order->status !== Order::STATUS_BUYER_INSPECTION_COMPLETE ||
                $order->buyerDeliveryOffer
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $buyerDeliveryOffer = new BuyerDeliveryOffer();
            $buyerDeliveryOffer->load($params, '');
            $buyerDeliveryOffer->created_at = date('Y-m-d H:i:s');
            $buyerDeliveryOffer->manager_id = $user->id;
            $buyerDeliveryOffer->buyer_id = $order->buyer_id;
            $buyerDeliveryOffer->status = BuyerDeliveryOffer::STATUS_CREATED;
            $buyerDeliveryOffer->price_product = $params['price_product'];
            $buyerDeliveryOffer->currency = $user->settings->currency;

            if (!$buyerDeliveryOffer->save()) {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $buyerDeliveryOffer->getFirstErrors(),
                );
            }

            // Устанавливаем флаг наличия накладной для заказа
            $order->waybill_isset = true;
            if (!$order->save()) {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $order->getFirstErrors(),
                );
            }

            // Подготавливаем данные для накладной
            $waybillData = array_merge($params, [
                'buyer_id' => $order->buyer_id,
                'client_id' => $order->client_id,
                'manager_id' => $user->id,
                'parent_category' => $order->category ?
                    ($order->category->parent ? $order->category->parent->name : $order->category->name)
                    : '',
            ]);

            // Получаем актуальный курс
            $rate = Rate::find()->orderBy(['id' => SORT_DESC])->one();
            $waybillData['course'] = $rate ? $rate->USD : 1;

            // Создаем накладную через сервис
            try {
                $waybill = WaybillService::create($waybillData);
            } catch (\Exception $e) {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    ['waybill' => $e->getMessage()]
                );
            }

            return ApiResponse::info(
                BuyerDeliveryOfferOutputService::getEntity(
                    $buyerDeliveryOffer->id,
                ),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/manager/order/buyer-delivery-offer/paid/{id}",
     *     summary="Отметить предложение по доставке как оплаченное",
     *     tags={"BuyerDeliveryOffer"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID предложения по доставке",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Предложение по доставке успешно отмечено как оплаченное"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="П��едложение по доставке не найдено"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Нет доступа к предложению"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера"
     *     )
     */
    public function actionPaid(int $id)
    {
        try {
            $apiCodes = Order::apiCodes();
            $user = User::getIdentity();

            $buyerDeliveryOffer = BuyerDeliveryOffer::findOne(['id' => $id]);
            Log::log('buyerDeliveryOffer: ' . json_encode($buyerDeliveryOffer));
            if (!$buyerDeliveryOffer) {
                Log::danger('buyerDeliveryOffer not found');
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            $order = $buyerDeliveryOffer->order;
            Log::log('order: ' . json_encode($order));

            if (
                $order->manager_id !== $user->id ||
                $buyerDeliveryOffer->status === BuyerDeliveryOffer::STATUS_PAID
            ) {
                Log::danger('no access');
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $transaction = Yii::$app->db->beginTransaction();

            $buyerDeliveryOffer->status = BuyerDeliveryOffer::STATUS_PAID;
            Log::log('buyerDeliveryOffer status: ' . $buyerDeliveryOffer->status);

            if (!$buyerDeliveryOffer->save()) {
                Log::danger('buyerDeliveryOffer save error: ' . json_encode($buyerDeliveryOffer->getFirstErrors()));
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $buyerDeliveryOffer->getFirstErrors(),
                );
            }

            $rate = Rate::find()
                ->orderBy(['id' => SORT_DESC])
                ->one();

            $orderRate = new OrderRate();
            $orderRate->order_id = $order->id;
            $orderRate->RUB = $rate->RUB;
            $orderRate->CNY = $rate->CNY;
            $orderRate->USD = $rate->USD;
            $orderRate->type = OrderRate::TYPE_PRODUCT_DELIVERY_PAYMENT;

            if (!$orderRate->save()) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $orderRate->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                BuyerDeliveryOfferOutputService::getEntity($id),
            );
        } catch (Throwable $e) {
            Log::danger('error: ' . json_encode($e->getMessage()));

            isset($transaction) && $transaction->rollBack();
            return ApiResponse::internalError($e);
        }
    }
}
