<?php

namespace app\controllers\api\v1\service;

use app\components\ApiResponse;
use app\controllers\api\v1\ServiceController;
use app\models\User;
use app\services\output\ProfileOutputService;
use Throwable;
use Yii;

class ProfileController extends ServiceController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['info'] = ['get'];

        return $behaviors;
    }

    public function actionInfo()
    {
        try {
            $userToken = Yii::$app->request->get('user_token');
            $user = User::find()
                ->select(['id'])
                ->where(['access_token' => $userToken])
                ->one();
            $apiCodes = User::apiCodes();

            if (!$user) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            return ApiResponse::info(
                ProfileOutputService::getEntity($user->id),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }
}
