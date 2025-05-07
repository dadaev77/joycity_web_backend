<?php

namespace app\controllers\api\v1\client;

use app\components\ApiResponse;
use app\controllers\api\v1\ClientController;
use app\services\WaybillService;
use app\models\Order;
use app\models\User;

class WaybillController extends ClientController
{
    protected $path;

    public function __construct($id, $module = null, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->path = $_ENV['APP_URL'] . '/uploads/waybills/';
    }

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
                'Заказ не найден',
                "ID заказа: {$id}",
                "Клиент: {$user->name} (ID: {$user->id})"
            ], 'client');

            \Yii::$app->telegramLog->sendAlert('critical', [
                'Заказ не найден',
                "ID заказа: {$id}",
                "Клиент: {$user->name} (ID: {$user->id})"
            ], 'critical');

            return ApiResponse::code($apiCodes->NOT_FOUND, ['message' => 'Заказ не найден']);
        };
        if ($order->created_by !== $user->id) {

            \Yii::$app->telegramLog->send('error', [
                'Нет доступа к накладной',
                "ID заказа: {$id}",
                "Клиент: {$user->name} (ID: {$user->id})"
            ], 'client');

            \Yii::$app->telegramLog->sendAlert('critical', [
                'Нет доступа к накладной',
                "ID заказа: {$id}",
                "Клиент: {$user->name} (ID: {$user->id})"
            ], 'critical');

            return ApiResponse::code($apiCodes->NO_ACCESS, ['message' => 'Нет доступа к накладной']);
        }

        $waybill = $order->waybill;
        
        if ($waybill) {

            if (
                !$waybill->editable &&
                $waybill->block_edit_date
            ) {
                $blockEditDate = new \DateTime($waybill->block_edit_date);
                $currentDate = new \DateTime();
                $interval = $currentDate->diff($blockEditDate);

                if ($interval->days > 2) {
                    return ApiResponse::code($apiCodes->SUCCESS, [
                        'waybill_path' => $this->path . $waybill->file_path,
                    ]);
                } else {
                    return ApiResponse::code($apiCodes->NO_ACCESS, [
                        'message' => 'Накладная еще недоступна для просмотра'
                    ]);
                }
            }
        }

        \Yii::$app->telegramLog->send('error', [
            'Накладная не найдена',
            "ID заказа: {$id}",
            "Клиент: {$user->name} (ID: {$user->id})"
        ], 'client');

        \Yii::$app->telegramLog->sendAlert('critical', [
            'Накладная не найдена',
            "ID заказа: {$id}",
            "Клиент: {$user->name} (ID: {$user->id})"
        ], 'critical');

        return ApiResponse::code($apiCodes->NOT_FOUND, ['message' => 'Накладная не найдена']);
    }
}
