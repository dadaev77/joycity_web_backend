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
        $path = $_ENV['APP_URL'] . '/uploads/waybills/' . $waybill->file_path;
        return
            ApiResponse::byResponseCode($apiCodes->SUCCESS, [
                'waybill' => $waybill,
            ]);
        // return $id;
    }
}
