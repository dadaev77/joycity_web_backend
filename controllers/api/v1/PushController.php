<?php

namespace app\controllers\api\v1;

use app\controllers\api\V1Controller;

class PushController extends V1Controller
{
    protected $pushService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        return $behaviors; 
    }
    public function actionSend()
    {
        return [
            'status' => 'success',
            'message' => 'Push notification sent successfully',
        ];
    }
} 