<?php

namespace app\controllers\api\v1\buyer;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\controllers\api\v1\BuyerController;
use app\models\Base;
use app\models\Order;
use app\models\ProductInspectionReport;
use app\models\ProductStockReport;
use app\models\TypeDeliveryPoint;
use app\models\User;
use app\services\AttachmentService;
use app\services\order\OrderStatusService;
use app\services\OrderTrackingConstructorService;
use app\services\output\OrderOutputService;
use app\services\output\ProductInspectionOutputService;
use app\services\output\ProductStockReportOutputService;
use Throwable;
use Yii;
use yii\web\UploadedFile;

class ReportController extends BuyerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['submit-inspection'] = ['post'];
        $behaviors['verbFilter']['actions']['submit-stock-report'] = ['post'];
        $behaviors['verbFilter']['actions']['order-sent'] = ['post'];
        array_unshift($behaviors['access']['rules'], [
            'actions' => ['submit-inspection', 'submit-stock-report', 'order-sent'],
            'allow' => false,
            'matchCallback' => fn() => User::getIdentity()->is([
                User::ROLE_BUYER_DEMO
            ]),
        ]);
        $behaviors['access']['denyCallback'] = static function () {
            $response =
                User::getIdentity()->is([
                    User::ROLE_BUYER_DEMO
                ]) ?
                ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_AUTHENTICATED) :
                false;
            Yii::$app->response->data = $response;
        };
        return $behaviors;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/buyer/report/submit-stock-report",
     *     summary="Отправить отчет о запасах",
     *     @OA\Response(response="200", description="Успешный ответ"),
     *     @OA\Response(response="400", description="Ошибка валидации"),
     *     @OA\Response(response="500", description="Ошибка сервера")
     * )
     */
    public function actionSubmitStockReport()
    {
        try {
            $request = Yii::$app->request;
            $apiCodes = ProductStockReport::apiCodes();
            $order_id = $request->post('order_id');
            $user = User::getIdentity();

            $existingInspection = ProductStockReport::findOne([
                'order_id' => $order_id,
            ]);

            if ($existingInspection) {
                return ApiResponse::code(
                    $apiCodes->DUPLICATE_ENTRY_BUYER_STOCK_REPORT,
                );
            }

            $order = Order::findOne(['id' => $order_id]);

            if (!$order) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (
                $order->status !== Order::STATUS_TRANSFERRING_TO_BUYER ||
                $order->buyer_id !== $user->id
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $order->delivery_start_date = date('Y-m-d H:i:s');

            /**
             * TODO: автоматизировать назначение срока доставки
             * $order->delivery_days_expected = $request->post('delivery_days_expected');
             * $order->delivery_delay_days = $request->post('delivery_delay_days');
             */

            if (!$order->save()) {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $order->getFirstErrors(),
                );
            }

            $transaction = null;

            $stockReport = new ProductStockReport();

            $stockReport->order_id = $order_id;
            $stockReport->created_at = date('Y-m-d H:i:s');

            if (!$stockReport->validate()) {
                return ApiResponse::codeErrors(
                    $apiCodes->NOT_VALID,
                    $stockReport->getFirstErrors(),
                );
            }

            $transaction = Yii::$app->db->beginTransaction();

            if (!$stockReport->save()) {
                $transaction?->rollBack();

                Yii::$app->telegramLog->send('error', [
                    'Не удалось создать отчет о наличии товара',
                    'Текст ошибки: ' . json_encode($stockReport->getFirstErrors()),
                ], 'buyer');

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $stockReport->getFirstErrors(),
                );
            }

            $status = OrderStatusService::arrivedToBuyer($order->id);

            if (!$status->success) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $status->reason,
                );
            }

            $images = UploadedFile::getInstancesByName('images');

            if ($images) {
                $attachmentSaveResponse = AttachmentService::writeFilesCollection(
                    $images,
                );

                if (!$attachmentSaveResponse->success) {
                    $transaction?->rollBack();

                    return ApiResponse::byResponseCode(
                        $apiCodes->INTERNAL_ERROR,
                        [
                            'errors' => [
                                'images' => 'Не удалось сохранить картинку',
                            ],
                        ],
                    );
                }

                $stockReport->linkAll(
                    'attachments',
                    $attachmentSaveResponse->result,
                );
            }

            $status = OrderTrackingConstructorService::inBayerWarehouse(
                $order_id,
            );

            if ($status->isNotValid) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->NOT_VALID,
                    $status->reason,
                );
            }

            if (!$status->success) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $status->reason,
                );
            }

            $transaction?->commit();

            \Yii::$app->telegramLog->send('success', [
                'Товар прибыл на склад продавца',
                'ID отчета: ' . $stockReport->id,
                'ID заказа: ' . $order_id,
                'ID покупателя: ' . $user->id,
            ], 'buyer');

            return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
                'info' => ProductStockReportOutputService::getEntity(
                    $stockReport->id,
                ),
            ]);
        } catch (Throwable $e) {
            Yii::$app->telegramLog->send('error', [
                'Ошибка при отправке отчета о запасах',
                'Текст ошибки: ' . $e->getMessage(),
                'Трассировка: ' . $e->getTraceAsString(),
            ], 'buyer');

            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/buyer/report/submit-inspection",
     *     summary="Отправить отчет о проверке",
     *     @OA\Response(response="200", description="Успешный ответ"),
     *     @OA\Response(response="400", description="Ошибка валидации"),
     *     @OA\Response(response="500", description="Ошибка сервера")
     * )
     */
    public function actionSubmitInspection()
    {
        try {
            $apiCodes = ProductInspectionReport::apiCodes();
            $user = Yii::$app->user->identity;
            $request = Yii::$app->request;
            $order_id = $request->post('order_id');

            $existingInspection = ProductInspectionReport::findOne([
                'order_id' => $order_id,
            ]);

            if ($existingInspection) {
                return ApiResponse::code(
                    $apiCodes->DUPLICATE_ENTRY_BUYER_SUBMIT_INSPECTION,
                );
            }

            $order = Order::findOne([
                'id' => $order_id,
                'buyer_id' => $user->id,
            ]);

            if (!$order) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (
                $order->status !== Order::STATUS_ARRIVED_TO_BUYER ||
                $order->buyer_id !== $user->id
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $transaction = Yii::$app->db->beginTransaction();
            $productInspection = new ProductInspectionReport();

            $productInspection->load($request->post(), '');
            $productInspection->created_at = date('Y-m-d H:i:s');
            $productInspection->is_deep = $order->is_need_deep_inspection;

            if (!$productInspection->save()) {
                Yii::$app->telegramLog->send('error', [
                    'Не удалось создать отчет о проверке',
                    'Текст ошибки: ' . json_encode($productInspection->getFirstErrors()),
                ], 'buyer');
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $productInspection->getFirstErrors(),
                );
            }

            $orderStatusChange = OrderStatusService::buyerInspectionComplete(
                $order->id,
            );

            if (!$orderStatusChange->success) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $orderStatusChange->reason,
                );
            }

            $transaction?->commit();

            \Yii::$app->telegramLog->send('success', [
                'Отчет об инспекции отправлен',
                'ID отчета: ' . $productInspection->id,
                'ID заказа: ' . $order_id,
                'ID покупателя: ' . $user->id,
            ], 'buyer');

            return ApiResponse::info(
                ProductInspectionOutputService::getEntity(
                    $productInspection->id,
                ),
            );
        } catch (Throwable $e) {
            Yii::$app->telegramLog->send('error', [
                'Ошибка при отправке отчета об инспекции',
                'Текст ошибки: ' . $e->getMessage(),
                'Трассировка: ' . $e->getTraceAsString(),
            ], 'buyer');
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/buyer/report/order-sent",
     *     summary="Отметить заказ как отправленный",
     *     @OA\Response(response="200", description="Успешный ответ"),
     *     @OA\Response(response="400", description="Ошибка валидации"),
     *     @OA\Response(response="500", description="Ошибка сервера")
     * )
     */
    public function actionOrderSent()
    {
        try {
            $apiCodes = Base::apiCodes();
            $request = Yii::$app->request;
            $user = User::getIdentity();
            $order_id = $request->post('order_id');
            $order = Order::findOne(['id' => $order_id]);

            if (!$order) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (
                $order->status !== Order::STATUS_BUYER_INSPECTION_COMPLETE ||
                $order->buyer_id !== $user->id ||
                !$order->buyerDeliveryOffer
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $transaction = Yii::$app->db->beginTransaction();

            if (
                $order->type_delivery_point_id ===
                TypeDeliveryPoint::TYPE_FULFILLMENT
            ) {
                $orderStatus = OrderStatusService::transferringToFulfilment(
                    $order->id,
                );

                if (!$orderStatus->success) {
                    return ApiResponse::transactionCodeErrors(
                        $transaction,
                        $apiCodes->ERROR_SAVE,
                        $orderStatus->reason,
                    );
                }
            } elseif (
                $order->type_delivery_point_id ===
                TypeDeliveryPoint::TYPE_WAREHOUSE
            ) {
                $orderStatus = OrderStatusService::transferringToWarehouse(
                    $order->id,
                );

                if (!$orderStatus->success) {
                    return ApiResponse::transactionCodeErrors(
                        $transaction,
                        $apiCodes->ERROR_SAVE,
                        $orderStatus->reason,
                    );
                }
            }

            $status = OrderTrackingConstructorService::sentDestination(
                $order_id,
            );

            if (!$status->success) {
                Yii::$app->telegramLog->send('error', [
                    'Не удалось создать отчет о отправке заказа',
                    'Текст ошибки: ' . json_encode($status->reason),
                ], 'buyer');
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $status->reason,
                );
            }

            $transaction?->commit();

            \Yii::$app->telegramLog->send('success', [
                'Заказ отправлен',
                'ID заказа: ' . $order_id,
                'ID покупателя: ' . $user->id,
            ], 'buyer');

            return ApiResponse::info(
                OrderOutputService::getEntity(
                    $order_id,
                    false, // Show deleted
                    'small', // Size of output images
                ),
            );
        } catch (Throwable $e) {
            Yii::$app->telegramLog->send('error', [
                'Ошибка при отправке заказа как отправленного',
                'Текст ошибки: ' . $e->getMessage(),
                'Трассировка: ' . $e->getTraceAsString(),
            ], 'buyer');
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }
}
