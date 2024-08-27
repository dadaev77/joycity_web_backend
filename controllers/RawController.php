<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\Order;
use yii\filters\VerbFilter;

class RawController extends Controller
{

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = ($action->id == "acceptFrontLogs");
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $orders = Order::find()->all();
        include(__DIR__ . '/../views/raw/index.php');
    }
    public function actionLog()
    {
        $logs = file_get_contents(__DIR__ . '/../runtime/logs/app.log');
        $tags = [
            'TWILIO_ACCOUNT_SID',
            'TWILIO_AUTH_TOKEN',
            'TWILIO_PHONE_NUMBER',
            'TWILIO_CONVERSATION_SERVICE_SID',
            'TWILIO_API_KEY_SID',
            'TWILIO_API_KEY_SECRET',
            'SCRIPT_NAME',
            'DOCUMENT_ROOT',
        ];
        $lines = explode("\n", $logs);
        foreach ($tags as $tag) {
            $logs = preg_replace('/.*' . preg_quote($tag, '/') . '.*\n?/', '', $logs);
        }

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

    public function actionAcceptFrontLogs()
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_JSON;
        $res = [
            'status' => false,
            'message' => 'Logs not accepted',
            'data' => null
        ];
        $request = Yii::$app->request;
        $data = $request->bodyParams;
        $logs = json_encode($data, JSON_PRETTY_PRINT);

        if (file_put_contents(__DIR__ . '/../runtime/logs/front.log', $logs, FILE_APPEND)) {
            $res['status'] = true;
            $res['message'] = 'Logs accepted';
            $res['data'] = $data;
        }

        // return response
        return $res;
    }
}
