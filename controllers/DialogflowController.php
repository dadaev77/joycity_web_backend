<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;

class DialogflowController extends Controller
{
    public function behaviors()
    {
        $this->enableCsrfValidation = false;
        return [
            'verbFilter' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'index' => ['get'],
                    'sendMessage' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        return 'index';
    }

    public function actionSendMessage()
    {
        if (!Yii::$app->request->isPost) {
            throw new \yii\web\MethodNotAllowedHttpException('Method Not Allowed. This endpoint only allows POST requests.');
        }
        $message = Yii::$app->request->post('message');
        return $message;
    }
}
