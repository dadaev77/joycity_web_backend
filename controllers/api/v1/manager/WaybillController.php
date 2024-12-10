<?php

namespace app\controllers\api\v1\manager;

use app\components\ApiResponse;
use app\controllers\api\v1\ManagerController;
use app\models\Order;
use app\services\WaybillService;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use Yii;
use app\models\Rate;
use app\services\UserActionLogService as Log;

/**
 * Контроллер для работы с накладными
 */
class WaybillController extends ManagerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions'] = [
            'generate' => ['post'],
            'view' => ['get'],
            'regenerate' => ['put'],
        ];

        return $behaviors;
    }

    public function actionGenerate()
    {
        try {
            $apiCodes = Order::apiCodes();
            $data = Yii::$app->request->post();

            Log::info('Генерация накладной: ' . json_encode($data));
            // Проверяем существование заказа
            $order = Order::findOne($data['order_id']);
            if (!$order) {
                throw new NotFoundHttpException('Заказ не найден');
            }

            // Добавляем необходимые данные для накладной
            $data['buyer_id'] = $order->buyer_id;
            $data['client_id'] = $order->created_by;
            $data['manager_id'] = $order->manager_id;

            // Получаем актуальный курс
            // $rate = Rate::find()->orderBy(['id' => SORT_DESC])->one();
            // $data['course'] = $rate ? $rate->USD : 1;

            // Получаем родительскую категорию
            if ($order->category) {
                $data['parent_category'] = $order->category->parent ?
                    $order->category->parent->name :
                    $order->category->name;
            }

            $firstAttachment = $order->getFirstAttachment();

            $uploadDir = Yii::getAlias('@webroot/uploads/');
            if ($firstAttachment && file_exists($uploadDir.$firstAttachment->path)) {
                $fileContents = file_get_contents($uploadDir.$firstAttachment->path);
                $base64Image = 'data:' . $firstAttachment->mime_type . ';base64,' . base64_encode($fileContents);
                $data['first_attachment'] = $base64Image;
            } else {
                $data['first_attachment'] = null; // Если файла нет или он недоступен
            }

            $waybill = WaybillService::update($order->waybill, $data);

            return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
                'invoice' => $_ENV['APP_URL'] . '/uploads/waybills/' . $waybill->file_path,
                'waybill' => $waybill
            ]);
        } catch (\Exception $e) {
            return ApiResponse::byResponseCode($apiCodes->ERROR_SAVE, [
                'message' => $e->getMessage()
            ]);
        }
    }

    public function actionView($id)
    {
        try {
            $apiCodes = Order::apiCodes();

            // Получаем накладную через сервис
            $waybill = WaybillService::getByOrderId($id);
            $waybill->date_of_production = date('Y-m-d', strtotime($waybill->date_of_production));
            $waybill->file_path = $_ENV['APP_URL'] . '/uploads/waybills/' . $waybill->file_path;

            return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
                'waybill' => $waybill,
            ]);
        } catch (NotFoundHttpException $e) {
            return ApiResponse::byResponseCode($apiCodes->NOT_FOUND, [
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return ApiResponse::byResponseCode($apiCodes->ERROR_SAVE, [
                'message' => $e->getMessage()
            ]);
        }
    }

    public function actionEditBan()
    {
        try {
            $apiCodes = Order::apiCodes();
            $orderId = Yii::$app->request->post('order_id');

            if (!$orderId) {
                throw new BadRequestHttpException('Не указан ID заказа');
            }

            // Блокируем редактирование через сервис
            $waybill = WaybillService::lockEditing($orderId);

            return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
                'waybill' => WaybillService::formatFilePath($waybill)
            ]);
        } catch (NotFoundHttpException $e) {
            return ApiResponse::byResponseCode($apiCodes->NOT_FOUND, [
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return ApiResponse::byResponseCode($apiCodes->ERROR_SAVE, [
                'message' => $e->getMessage()
            ]);
        }
    }
}
