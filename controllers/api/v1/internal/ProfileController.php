<?php

namespace app\controllers\api\v1\internal;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\services\output\ProfileOutputService;
use Yii;

class ProfileController extends InternalController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['self'] = ['get'];

        return $behaviors;
    }

    public function actionSelf()
    {
        $userId = Yii::$app->user->identity;

        return ApiResponse::info(ProfileOutputService::getEntity($userId->id));
    }
}
