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

    /**
     * @OA\Post(
     *     path="/api/v1/buyer/order/buyer-offer/create",
     *     summary="Создать новое предложение покупателя",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id", "price_product", "price_inspection", "total_quantity"},
     *             @OA\Property(property="order_id", type="integer", example=1),
     *             @OA\Property(property="price_product", type="number", format="float", example=99.99),
     *             @OA\Property(property="price_inspection", type="number", format="float", example=9.99),
     *             @OA\Property(property="total_quantity", type="integer", example=10),
     *             @OA\Property(property="product_height", type="number", format="float", example=1.5),
     *             @OA\Property(property="product_width", type="number", format="float", example=2.0),
     *             @OA\Property(property="product_depth", type="number", format="float", example=3.0),
     *             @OA\Property(property="product_weight", type="number", format="float", example=1.0)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Предложение успешно создано."
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Неверный запрос."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера."
     *     )
     * )
     */
    public function actionCreate()
    {
        $apiCodes = BuyerOffer::apiCodes();

        try {
            $postData = Yii::$app->request->post();
            $user = User::getIdentity();
            $order = Order::findOne(['id' => $postData['order_id']]) ?? null;

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
                'price_product' => $postData['price_product'] ?? 0,
                'price_inspection' => $postData['price_inspection'] ?? 0,
                'total_quantity' => $postData['total_quantity'] ?? 0,
                'product_height' => $postData['product_height'] ?? 0,
                'product_width' => $postData['product_width'] ?? 0,
                'product_depth' => $postData['product_depth'] ?? 0,
                'product_weight' => $postData['product_weight'] ?? 0,
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
            Yii::$app->telegramLog->send('error', 'Ошибка при создании предложения продавца: ' . $e->getMessage());
            isset($transaction) && $transaction->rollBack();
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/buyer/order/buyer-offer/update/{id}",
     *     summary="Обновить предложение покупателя",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID предложения.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"price_product", "price_inspection", "total_quantity"},
     *             @OA\Property(property="price_product", type="number", format="float", example=89.99),
     *             @OA\Property(property="price_inspection", type="number", format="float", example=8.99),
     *             @OA\Property(property="total_quantity", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Предложение успешно обновлено."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Предложение не найдено."
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Нет доступа к предложению."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера."
     *     )
     * )
     */
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

            $buyerOffer->price_product = $params['price_product'];

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

    /**
     * @OA\Get(
     *     path="/api/v1/buyer/order/buyer-offer/view/{id}",
     *     summary="Получить информацию о предложении покупателя",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID предложения.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Информация о предложении успешно получена."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Предложение не найдено."
     *     )
     * )
     */
    public function actionView(int $id)
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

            return ApiResponse::info(
                BuyerOfferOutputService::getEntity($buyerOffer->id),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/buyer/order/buyer-offer/delete/{id}",
     *     summary="Удалить предложение покупателя",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID предложения.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Предложение успешно удалено."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Предложение не найдено."
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Нет доступа к предложению."
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v1/buyer/order/buyer-offer/my",
     *     summary="Получить мои предложения",
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         required=false,
     *         description="Смещение для пагинации.",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список предложений успешно получен."
     *     )
     * )
     */
    public function actionMy(int $offset = 0)
    {
        $apiCodes = BuyerOffer::apiCodes();

        try {
            $user = User::getIdentity();
            $buyerOffers = BuyerOffer::find()
                ->where(['buyer_id' => $user->id])
                ->offset($offset)
                ->limit(10)
                ->all();

            if (!$buyerOffers) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            return ApiResponse::info(
                BuyerOfferOutputService::getEntities($buyerOffers),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }
}
