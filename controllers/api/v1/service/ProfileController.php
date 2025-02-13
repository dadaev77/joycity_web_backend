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

    /**
     * @OA\Get(
     *   path="/api/v1/service/profile/info",
     *   summary="Get user info",
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
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
            \Yii::$app->telegramLog->send('error', 'Error in ProfileController::actionInfo: ' . $e->getMessage());
            return ApiResponse::internalError($e);
        }
    }
}
