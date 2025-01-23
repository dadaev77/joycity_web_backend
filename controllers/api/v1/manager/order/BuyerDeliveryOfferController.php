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
                    "total_quantity",
                    "package_expenses",
                    "product_depth",
                    "product_width",
                    "product_height",
                    "product_weight",
                    "amount_of_space",
                    "price_product",
                    "order_id",
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
            $order->client_waybill_isset = true;
            if (!$order->save()) {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $order->getFirstErrors(),
                );
            }

            // Подготавливаем данные для накладной
            $waybillData = array_merge($params, [
                'buyer_id' => $order->buyer_id,
                'client_id' => $order->created_by,
                'manager_id' => $user->id
            ]);

            $firstAttachment = $order->getFirstAttachment();
            $uploadDir = Yii::getAlias('@webroot/uploads/');
            if ($firstAttachment && file_exists($uploadDir . $firstAttachment->path)) {
                $fileContents = file_get_contents($uploadDir . $firstAttachment->path);
                $base64Image = 'data:' . $firstAttachment->mime_type . ';base64,' . base64_encode($fileContents);
                $waybillData['first_attachment'] = $base64Image;
            } else {
                $waybillData['first_attachment'] = null; // Если файла нет или он недоступен
            }

            // Создаем накладную через сервис
            $waybill = WaybillService::create($waybillData);
            sleep(1); // TODO: Удалить
            return ApiResponse::info(
                BuyerDeliveryOfferOutputService::getEntity(
                    $buyerDeliveryOffer->id,
                ),
            );
        } catch (Throwable $e) {
            Yii::$app->telegramLog->send('error', 'Ошибка при создании предложения по доставке: ' . $e->getMessage());
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
     *         description="Предложение по доставке не найдено"
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
            if (!$buyerDeliveryOffer) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            $order = $buyerDeliveryOffer->order;

            if (
                $order->manager_id !== $user->id ||
                $buyerDeliveryOffer->status === BuyerDeliveryOffer::STATUS_PAID
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $transaction = Yii::$app->db->beginTransaction();

            $buyerDeliveryOffer->status = BuyerDeliveryOffer::STATUS_PAID;

            if (!$buyerDeliveryOffer->save()) {
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
            Yii::$app->telegramLog->send('error', 'Ошибка при оплате предложения по доставке: ' . $e->getMessage());
            isset($transaction) && $transaction->rollBack();
            return ApiResponse::internalError($e);
        }
    }
}
