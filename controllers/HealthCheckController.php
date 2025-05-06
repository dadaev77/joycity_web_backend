<?php

namespace app\controllers;

use yii\rest\Controller;

class HealthCheckController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        return $behaviors;
    }

    public function actionChats()
    {
        return $this->asJson('Chats is ok')->setStatusCode(200);
    }
    public function actionQueue()
    {
        return $this->asJson('Queue is ok')->setStatusCode(200);
    }
    public function actionRates()
    {
        return $this->asJson('Rates is ok')->setStatusCode(200);
    }
    public function actionAzure()
    {
        return $this->asJson('Azure is ok')->setStatusCode(200);
    }
    public function actionWebSockets()
    {
        return $this->asJson('WebSockets is ok')->setStatusCode(200);
    }
    public function actionTelegram()
    {
        return $this->asJson('Telegram is ok')->setStatusCode(200);
    }
}
