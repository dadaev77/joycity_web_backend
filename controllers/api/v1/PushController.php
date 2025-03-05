<?php

namespace app\controllers\api\v1;

use app\controllers\api\V1Controller;
use app\services\push\PushService;
use app\components\ApiResponse;

use Yii;

/**
 * @OA\Tag(
 *     name="Push",
 *     description="API для push-уведомлений"
 * )
 */
class PushController extends V1Controller
{

    protected $apiCodes;

    public function init()
    {
        parent::init();
        $this->apiCodes = \app\components\response\ResponseCodes::getStatic();
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        $behaviours['verbFilter']['actions']['send-firebase-notification'] = ['post'];
        $behaviours['verbFilter']['actions']['register-token'] = ['post'];
        $behaviours['verbFilter']['actions']['delete-token'] = ['delete'];
        $behaviours['verbFilter']['actions']['drop-tokens'] = ['delete'];
        
        return $behaviors; 
    }

    /**
     * @OA\Post(
     *     path="/api/v1/push/register-token",
     *     tags={"Push"},
     *     summary="Зарегистрировать токен push-уведомлений",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="push_token", type="string", example="ваш_push_токен"),
     *             @OA\Property(property="device_id", type="string", example="ваш_device_id")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Токен успешно зарегистрирован"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Недействительный токен"
     *     )
     * )
     */
    public function actionRegisterToken()
    {
        $token = Yii::$app->request->post('push_token');
        $deviceId = Yii::$app->request->post('device_id');
        $operatingSystem = Yii::$app->request->post('operating_system');
        
        if (!$token) return ApiResponse::codeErrors(
            $this->apiCodes->NOT_VALIDATED,
            ['Token is required']
        );
        
        return PushService::registerToken($token, $deviceId, $operatingSystem);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/push/delete-token",
     *     tags={"Push"},
     *     summary="Удалить токен push-уведомлений",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="push_token", type="string", example="ваш_push_токен")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Токен успешно удален"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Недействительный токен"
     *     )
     * )
     */

    public function actionDeleteToken()
    {
        $token = Yii::$app->request->post('push_token');

        if (!$token) return ApiResponse::codeErrors(
            $this->apiCodes->NOT_VALIDATED,
            ['Token is required']
        );

        return PushService::deleteToken($token);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/push/drop-tokens",
     *     tags={"Push"},
     *     summary="Удалить все токены для аутентифицированного пользователя",
     *     @OA\Response(
     *         response=200,
     *         description="Токены успешно удалены"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Пользователь не аутентифицирован"
     *     )
     * )
     */
    public function actionDropTokens()
    {
        $user = Yii::$app->user->getIdentity();

        if (!$user) return ApiResponse::codeErrors(
            $this->apiCodes->NOT_VALIDATED,
            ['User is required']
        );

        return PushService::dropTokens($user->id);
    }
} 