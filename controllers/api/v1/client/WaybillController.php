<?php

namespace app\controllers\api\v1\client;

use app\controllers\api\v1\ClientController;
use app\services\WaybillService;
use Yii;

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
        $waybill = WaybillService::getByOrderId($id);
        $path = Yii::getAlias('@webroot/uploads/waybills') . '/' . $waybill->file_path;;
        return $path;
    }
}
