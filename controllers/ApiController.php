<?php

namespace app\controllers;

use Yii;
use app\components\auth\HttpBearerAuthCustom;
use app\models\Base;
use yii\rest\ActiveController;
use yii\web\Response;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Joy City API",
 *     version="1.0.0",
 *     description="Документация API для приложения JoyCity"
 * )
 * @OA\PathItem(
 *   path="/api"
 * )
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT",
 *   description="JWT Authorization header using the Bearer scheme"
 * )
 */

class ApiController extends ActiveController
{
    public $modelClass = Base::class;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;
        $behaviors['authenticator'] = ['class' => HttpBearerAuthCustom::class];
        return $behaviors;
    }
}
