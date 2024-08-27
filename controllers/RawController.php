<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\Order;
use yii\filters\VerbFilter;
use app\models\User;
use app\models\Product;
use app\models\Order as OrderModel;

class RawController extends Controller
{
    protected const LOG_FILE = __DIR__ . '/../runtime/logs/app.log';
    protected const FRONT_LOG_FILE = __DIR__ . '/../runtime/logs/front.log';

    protected const KEYS = [
        'TWILIO_ACCOUNT_SID',
        'TWILIO_AUTH_TOKEN',
        'TWILIO_CONVERSATION_SERVICE_SID',
        'TWILIO_API_KEY_SID',
        'TWILIO_API_KEY_SECRET',
        'GATEWAY_INTERFACE',
        'CONTEXT_PREFIX',
        'SCRIPT_NAME',
        'PHP_SELF',
        'REQUEST_TIME_FLOAT',
        'REQUEST_TIME',
        'WEBSOCKET_CONTAINER_URL',
    ];

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
        $logs = file_get_contents(self::LOG_FILE);
        $frontLogs = file_get_contents(self::FRONT_LOG_FILE);
        $clients = User::find()->where(['role' => 'client'])->orderBy(['id' => SORT_DESC])->all();
        $managers = User::find()->where(['role' => 'manager'])->orderBy(['id' => SORT_DESC])->all();
        $fulfillment = User::find()->where(['role' => 'fulfillment'])->orderBy(['id' => SORT_DESC])->all();
        $buyers = User::find()->where(['role' => 'buyer'])->orderBy(['id' => SORT_DESC])->all();
        $products = Product::find()->orderBy(['id' => SORT_DESC])->all();
        $orders = OrderModel::find()->orderBy(['id' => SORT_DESC])->all();

        $keysToRemove = array_keys(array_intersect_key($_SERVER, array_flip(self::KEYS)));
        $lines = explode("\n", $logs);
        foreach ($keysToRemove as $key) {
            $logs = preg_replace('/.*' . preg_quote($key, '/') . '.*\n?/', '', $logs);
        }

        // limit to 1000 lines
        $logs = implode("\n", array_slice(explode("\n", $logs), -1000));
        $frontLogs = implode("\n", array_slice(explode("\n", $frontLogs), -1000));

        // format logs content
        $logs = nl2br($logs);
        $frontLogs = nl2br($frontLogs);

        // Render the log view with logs and frontLogs variables
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_HTML;
        return $this->renderPartial('log', [
            'logs' => $logs,
            'frontLogs' => $frontLogs,
            'clients' => $clients,
            'managers' => $managers,
            'fulfillment' => $fulfillment,
            'buyers' => $buyers,
            'products' => $products,
            'orders' => $orders,
        ], false);
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
        $request = Yii::$app->request;
        $data = $request->bodyParams;
        $logs = json_encode($data, JSON_PRETTY_PRINT);

        if (file_put_contents(__DIR__ . '/../runtime/logs/front.log', $logs, FILE_APPEND)) {
            $response->statusCode = 200;
            $response->data = [
                'status' => 'ok',
                'message' => 'Logs accepted'
            ];
        } else {
            $response->statusCode = 500;
            $response->data = [
                'status' => 'error',
                'message' => 'Logs not accepted'
            ];
        }
        return $response;
    }
}
