<?php

namespace app\controllers\api\v1\manager;

use app\components\ApiResponse;
use app\controllers\api\v1\ManagerController;
use Mpdf\Mpdf;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use app\models\Order;
use app\models\Waybill;

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
            $request = Yii::$app->request;
            $data = $request->post();

            // Проверяем существование заказа
            $order = Order::findOne($data['order_id']);
            if (!$order) throw new NotFoundHttpException('Заказ не найден');

            // Генерируем уникальное имя файла
            $fileName = 'waybill_' . uniqid() . '.pdf';
            $uploadDir = Yii::getAlias('@webroot/uploads/waybills');
            $filePath = $uploadDir . '/' . $fileName;

            // Создаем директорию с нужными правами если её нет
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    throw new \Exception('Не удалось создать директорию для сохранения файлов');
                }
                chmod($uploadDir, 0777);
            }

            if (!is_writable($uploadDir)) {
                throw new \Exception('Нет прав на запись в директорию: ' . $uploadDir);
            }

            // Генерируем PDF
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
            ]);

            // Рендерим шаблон с данными
            $content = Yii::$app->view->render('@app/views/pdf/templates/invoce', [
                'data' => $data,
            ]);

            $mpdf->WriteHTML($content);

            try {
                $mpdf->Output($filePath, 'F');
            } catch (\Exception $e) {
                throw new \Exception('Ошибка при сохранении PDF файла: ' . $e->getMessage());
            }
            $dateOfProduction = date('Y-m-d H:i:s', strtotime($data['date_of_production']));
            // Проверяем существование накладной для заказа
            $waybill = $order->waybill;

            if ($waybill) {

                if (file_exists($uploadDir . '/' . $waybill->file_path)) {
                    unlink($uploadDir . '/' . $waybill->file_path);
                }

                // Обновляем существующую накладную
                $waybill->price_per_kg = floatval($data['price_per_kg']);
                $waybill->course = floatval($data['course']);
                $waybill->total_number_pairs = intval($data['total_number_pairs']);
                $waybill->total_customs_duty = floatval($data['total_customs_duty']);
                $waybill->volume_costs = floatval($data['volume_costs']);
                $waybill->date_of_production = strval($dateOfProduction);
                $waybill->file_path = $fileName;
            } else {
                // Создаем новую накладную
                $waybill = new Waybill([
                    'order_id' => $data['order_id'],
                    'price_per_kg' => floatval($data['price_per_kg']),
                    'course' => floatval($data['course']),
                    'total_number_pairs' => intval($data['total_number_pairs']),
                    'total_customs_duty' => floatval($data['total_customs_duty']),
                    'volume_costs' => floatval($data['volume_costs']),
                    'date_of_production' => strval($dateOfProduction),
                    'file_path' => $fileName,
                ]);
            }

            if (!$waybill->save()) {
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                throw new \Exception('Ошибка при сохранении накладной в БД: ' . json_encode($waybill->errors));
            }

            $waybill->file_path = $_ENV['APP_URL'] . '/uploads/waybills/' . $waybill->file_path;

            return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
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
        $apiCodes = Order::apiCodes();
        $order = Order::findOne($id);

        if (!$order) {
            return ApiResponse::byResponseCode($apiCodes->NOT_FOUND, [
                'message' => 'Заявка не найдена'
            ]);
        }

        $waybill = $order->waybill;

        if (!$waybill) {
            return ApiResponse::byResponseCode($apiCodes->NOT_FOUND, [
                'message' => 'Накладная не найдена'
            ]);
        }
        $waybill->file_path = $_ENV['APP_URL'] . '/uploads/waybills/' . $waybill->file_path;
        return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
            'waybill' => $waybill
        ]);
    }


    public function actionEditBan()
    {
        try {
            $apiCodes = Order::apiCodes();
            $orderId = Yii::$app->request->post('order_id');

            if (!$orderId) {
                throw new BadRequestHttpException('Не указан ID заказа');
            }
            // Поиск заказа
            $order = Order::findOne($orderId);
            if (!$order) {
                throw new NotFoundHttpException('Заказ не найден');
            }
            // Получение накладной через связь
            $waybill = $order->waybill;
            if (!$waybill) {
                throw new NotFoundHttpException('У заказа нет доступной накладной');
            }
            // Установк флага и сохранение
            $waybill->editable = false;
            if (!$waybill->save()) {
                throw new \Exception('Ошибка при сохранении накладной');
            }

            $waybill->file_path = $_ENV['APP_URL'] . '/uploads/waybills/' . $waybill->file_path;

            return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
                'waybill' => $waybill
            ]);
        } catch (\Exception $e) {
            return ApiResponse::byResponseCode($apiCodes->ERROR_SAVE, [
                'message' => $e->getMessage()
            ]);
        }
    }
}
