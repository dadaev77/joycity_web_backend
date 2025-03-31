<?php

namespace app\controllers\api\v1\client;

use app\components\ApiResponse;
use app\controllers\api\v1\ClientController;
use app\services\WaybillService;
use app\models\Order;
use app\models\User;

class WaybillController extends ClientController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions'] = [
            'view' => ['get'],
        ];

        return $behaviors;
    }
    public function actionView($id)
    {
        $apiCodes = Order::apiCodes();
        $user = User::getIdentity();
        

        $order = Order::findOne(['id' => $id]);
        if (!$order) {
            \Yii::$app->telegramLog->send('error', [
                'Клиент пытается получить накладную несуществующего заказа',
                "ID заказа: {$id}",
                "Клиент: {$user->name} (ID: {$user->id})"
            ], 'client');
            return ApiResponse::code($apiCodes->NOT_FOUND, [
                'message' => 'Заказ не найден'
            ]);
        }


        if ($order->created_by !== $user->id) {
            \Yii::$app->telegramLog->send('error', [
                'Клиент пытается получить накладную чужого заказа',
                "ID заказа: {$id}",
                "Клиент: {$user->name} (ID: {$user->id})",
                "Создатель заказа: {$order->created_by}"
            ], 'client');
            return ApiResponse::code($apiCodes->NO_ACCESS, [
                'message' => 'Нет доступа к накладной'
            ]);
        }


        $waybill = WaybillService::getByOrderId($id);
        if (!$waybill) {
            \Yii::$app->telegramLog->send('error', [
                'Клиент пытается получить несуществующую накладную',
                "ID заказа: {$id}",
                "Клиент: {$user->name} (ID: {$user->id})"
            ], 'client');
            return ApiResponse::code($apiCodes->NOT_FOUND, [
                'message' => 'Накладная не найдена'
            ]);
        }


        if (!$waybill->editable && $waybill->block_edit_date) {
            $blockEditDate = new \DateTime($waybill->block_edit_date);
            $currentDate = new \DateTime();
            $interval = $currentDate->diff($blockEditDate);
            
            if ($interval->days > 2) {
                $path = $_ENV['APP_URL'] . '/uploads/waybills/' . $waybill->file_path;
                \Yii::$app->telegramLog->send('success', [
                    'Клиент успешно получил накладную',
                    "ID заказа: {$id}",
                    "Клиент: {$user->name} (ID: {$user->id})",
                    "Путь к накладной: {$path}"
                ], 'client');
                return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
                    'waybill_path' => $path,
                ]);
            }
        }

        \Yii::$app->telegramLog->send('warning', [
            'Клиент пытается получить недоступную накладную',
            "ID заказа: {$id}",
            "Клиент: {$user->name} (ID: {$user->id})",
            "Дата блокировки: {$waybill->block_edit_date}",
            "Прошло дней: {$interval->days}"
        ], 'client');
        return ApiResponse::code($apiCodes->NO_ACCESS, [
            'message' => 'Накладная еще недоступна для просмотра'
        ]);
    }
}
