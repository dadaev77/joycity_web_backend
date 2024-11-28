<?php

namespace app\controllers\api\v1\manager;

use app\components\ApiResponse;
use app\controllers\api\v1\ManagerController;
use Yii;

class WaybillController extends ManagerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['view'] = ['get'];

        return $behaviors;
    }
    public function actionIndex()
    {
        return 'index';
    }

    public function actionView($id)
    {
        //
    }
}
