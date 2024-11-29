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

    /**
     * @OA\Get(
     *     path="/api/v1/internal/profile/self",
     *     summary="Получить информацию о текущем пользователе",
     *     tags={"Profile"},
     *     security={{"Bearer":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Информация о пользователе успешно получена"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Пользователь не найден"
     *     )
     * )
     */
    public function actionSelf()
    {
        $userId = Yii::$app->user->identity;

        return ApiResponse::info(ProfileOutputService::getEntity($userId->id));
    }
}
