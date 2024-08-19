<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\Order;

class RawController extends Controller
{
    public function actionIndex()
    {
        $orders = Order::find()->all();
        include(__DIR__ . '/../views/raw/index.php');
    }
    public function actionLog()
    {
        $logs = file_get_contents(__DIR__ . '/../runtime/logs/app.log');
        if ($logs) {
            $logs = nl2br($logs);
            $logs = str_replace('', '<br>', $logs);
        } else {
            $logs = '<h2>Log file is empty</h2><br>';
        }
        Yii::$app->response->format = Response::FORMAT_HTML;
        include(__DIR__ . '/../views/raw/log.php');
    }
    public function actionGeneratePassword($password)
    {
        return Yii::$app
            ->getSecurity()
            ->generatePasswordHash($password);
    }
    public function actionClearLog()
    {
        if (file_put_contents(__DIR__ . '/../runtime/logs/app.log', '')) {
            return 'ok';
        }
        return 'error';
    }
}
