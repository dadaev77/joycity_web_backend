<?php

namespace app\controllers\api\v1\client;

use app\components\ApiResponse;
use app\controllers\api\v1\ClientController;
use app\services\WaybillService;
use app\models\Order;

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
        $waybill = WaybillService::getByOrderId($id);

        if ($waybill) {
            $path = $_ENV['APP_URL'] . '/uploads/waybills/' . $waybill->file_path;
        }

        if (!$waybill->editable && $waybill->block_edit_date) {
            $blockEditDate = new \DateTime($waybill->block_edit_date);
            $currentDate = new \DateTime();
            $interval = $currentDate->diff($blockEditDate);
            if (true) {
                return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
                    'waybill_path' => $path,
                ]);
            }
        }
    }
}
