<?php

namespace app\controllers\api\v1\fulfillment;

use app\components\ApiResponse;
use app\controllers\api\v1\FulfillmentController;
use app\models\FulfillmentInspectionReport;
use app\models\FulfillmentPackagingLabeling;
use app\models\FulfillmentStockReport;
use app\models\Order;
use app\models\User;
use app\services\AttachmentService;
use app\services\order\OrderStatusService;
use app\services\OrderTrackingConstructorService;
use app\services\output\FulfillmentInspectionOutputService;
use app\services\output\FulfillmentPackagingLabelingOutputService;
use app\services\output\FulfillmentStockReportOutputService;
use Throwable;
use Yii;
use yii\web\UploadedFile;

class ReportController extends FulfillmentController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['submit-inspection'] = ['post'];
        $behaviors['verbFilter']['actions']['submit-stock-report'] = ['post'];
        $behaviors['verbFilter']['actions']['order-sent'] = ['post'];
        $behaviors['verbFilter']['actions']['submit-packaging-report'] = ['post'];

        return $behaviors;
    }

    public function actionSubmitStockReport()
    {
        try {
            $request = Yii::$app->request;
            $apiCodes = FulfillmentStockReport::apiCodes();
            $order_id = $request->post('order_id');
            $user = User::getIdentity();

            $existingInspection = FulfillmentStockReport::findOne([
                'order_id' => $order_id,
            ]);
            $order = Order::findOne(['id' => $order_id]);

            if (!$order) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (
                $order->status !== Order::STATUS_TRANSFERRING_TO_FULFILLMENT ||
                $order->fulfillment_id !== $user->id
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            if ($existingInspection) {
                return ApiResponse::code($apiCodes->DUPLICATE_ENTRY_FULFILLMENT_STOCK_REPORT);
            }

            $transaction = null;

            $stockReport = new FulfillmentStockReport();

            $stockReport->order_id = $order_id;
            $stockReport->created_at = date('Y-m-d H:i:s');
            $stockReport->fulfillment_id = $user->id;

            if (!$stockReport->validate()) {
                return ApiResponse::codeErrors(
                    $apiCodes->NOT_VALID,
                    $stockReport->getFirstErrors(),
                );
            }

            $transaction = Yii::$app->db->beginTransaction();

            if (!$stockReport->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $stockReport->getFirstErrors(),
                );
            }

            $status = OrderStatusService::arrivedToFulfilment($order->id);

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

                    return ApiResponse::codeErrors(
                        $apiCodes->INTERNAL_ERROR,
                        ['images' => 'Не удалось сохранить картинку'],
                    );
                }

                $stockReport->linkAll(
                    'attachments',
                    $attachmentSaveResponse->result,
                );
            }

            $status = OrderTrackingConstructorService::inFulfillmentWarehouse(
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

            return ApiResponse::info(
                FulfillmentStockReportOutputService::getEntity($stockReport->id),
            );
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }

    public function actionSubmitInspection()
    {
        try {
            $apiCodes = FulfillmentInspectionReport::apiCodes();
            $user = User::getIdentity();
            $request = Yii::$app->request;
            $order_id = $request->post('order_id');

            $existingInspection = FulfillmentInspectionReport::findOne([
                'order_id' => $order_id,
            ]);
            $order = Order::findOne([
                'id' => $order_id,
                'fulfillment_id' => $user->id,
            ]);

            if (!$order) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (
                $order->status !== Order::STATUS_ARRIVED_TO_FULFILLMENT ||
                $order->fulfillment_id !== $user->id
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            if ($existingInspection) {
                return ApiResponse::code($apiCodes->DUPLICATE_ENTRY_FULFILLMENT_SUBMIT_INSPECTION);
            }

            $transaction = Yii::$app->db->beginTransaction();
            $productInspection = new FulfillmentInspectionReport();

            $productInspection->load($request->post(), '');
            $productInspection->created_at = date('Y-m-d H:i:s');
            $productInspection->is_deep = $order->is_need_deep_inspection;
            $productInspection->fulfillment_id = $user->id;

            if (!$productInspection->save()) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $productInspection->getFirstErrors(),
                );
            }

            $orderStatusChange = OrderStatusService::fulfillmentInspectionComplete(
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

            return ApiResponse::info(
                FulfillmentInspectionOutputService::getEntity(
                    $productInspection->id,
                ),
            );
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }

    public function actionSubmitPackagingReport()
    {
        try {
            $request = Yii::$app->request;
            $apiCodes = FulfillmentPackagingLabeling::apiCodes();
            $order_id = $request->post('order_id');
            $user = User::getIdentity();

            $existingInspection = FulfillmentPackagingLabeling::findOne([
                'order_id' => $order_id,
            ]);
            $order = Order::findOne(['id' => $order_id]);

            if (!$order) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (
                $order->status !==
                    Order::STATUS_FULFILLMENT_INSPECTION_COMPLETE ||
                $order->fulfillment_id !== $user->id
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            if ($existingInspection) {
                return ApiResponse::code($apiCodes->DUPLICATE_ENTRY_FULFILLMENT_PACKAGING_LABELING);
            }

            $transaction = null;

            $stockReport = new FulfillmentPackagingLabeling();

            $stockReport->order_id = $order_id;
            $stockReport->created_at = date('Y-m-d H:i:s');
            $stockReport->fulfillment_id = $user->id;
            $stockReport->load($request->post(), '');
            if (!$stockReport->validate()) {
                return ApiResponse::codeErrors(
                    $apiCodes->NOT_VALID,
                    $stockReport->getFirstErrors(),
                );
            }

            $transaction = Yii::$app->db->beginTransaction();

            if (!$stockReport->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $stockReport->getFirstErrors(),
                );
            }

            $status = OrderStatusService::fulfillmentPackageLabelingComplete($order->id);

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

                    return ApiResponse::codeErrors(
                        $apiCodes->INTERNAL_ERROR,
                        ['images' => 'Не удалось сохранить картинку']);
                }

                $stockReport->linkAll(
                    'attachments',
                    $attachmentSaveResponse->result,
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                FulfillmentPackagingLabelingOutputService::class::getEntity($stockReport->id),
            );
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }
}
