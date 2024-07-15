<?php

namespace app\controllers;

use Yii;
use app\components\auth\HttpBearerAuthCustom;
use app\models\Base;
use yii\rest\ActiveController;
use yii\web\Response;

class ApiController extends ActiveController
{
    public $modelClass = Base::class;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;
        $behaviors['authenticator'] = ['class' => HttpBearerAuthCustom::class];
        $behaviors['authenticator']['except'] = ['admin'];

        return $behaviors;
    }

    public function actionAdmin()
    {
        Yii::$app->response->format = Response::FORMAT_HTML;
        return $this->renderPartial('admin');
    }
}
