<?php

namespace app\controllers;

use app\models\Order;
use yii\web\Controller;
use app\components\ApiResponse;

class RawController extends Controller
{
    public function actionIndex()
    {
        $order = Order::find()->all();
        return ApiResponse::collection($order);
    }
}
