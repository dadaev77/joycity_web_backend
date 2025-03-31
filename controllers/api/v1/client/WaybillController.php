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

        if ($order->created_by !== $user->id) return ApiResponse::code($apiCodes->NO_ACCESS, ['message' => 'Нет доступа к накладной']);
        $waybill = $order->waybill;
        if (!$waybill) return ApiResponse::code($apiCodes->NOT_FOUND, ['message' => 'Накладная не найдена']);

        if ($waybill) {
            return 'waybill is here';
            if (!$waybill->editable && $waybill->block_edit_date) {
                $blockEditDate = new \DateTime($waybill->block_edit_date);
                $currentDate = new \DateTime();
                $interval = $currentDate->diff($blockEditDate);

                if ($interval->days > 2) {
                    $path = $_ENV['APP_URL'] . '/uploads/waybills/' . $waybill->file_path;
                    return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
                        'waybill_path' => $path,
                    ]);
                } else {
                    return ApiResponse::code($apiCodes->NO_ACCESS, [
                        'message' => 'Накладная еще недоступна для просмотра'
                    ]);
                }
            }
        }
        return ApiResponse::code($apiCodes->NOT_FOUND, ['message' => 'Накладная не найдена']);
    }
}
