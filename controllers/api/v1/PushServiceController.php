<?php

namespace app\controllers\api\v1;

use Yii;
use app\services\PushService;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;

/**
 * @OA\Tag(
 *     name="push-services",
 *     description="API для управления push-уведомлениями"
 * )
 */
class PushServiceController extends Controller
{
    private $pushService;

    public function __construct($id, $module, PushService $pushService, $config = [])
    {
        $this->pushService = $pushService;
        parent::__construct($id, $module, $config);
    }


    /**
     * @OA\Post(
     *     path="/api/v1/push-services/register",
     *     tags={"push-services"},
     *     summary="Регистрация устройства",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"client_id","device_id"},
     *             @OA\Property(property="client_id", type="string"),
     *             @OA\Property(property="device_id", type="string"),
     *             @OA\Property(property="push_token", type="string"),
     *             @OA\Property(property="platform", type="string", enum={"ios", "android"})
     *         )
     *     ),
     *     @OA\Response(response="200", description="Успешная регистрация")
     * )
     */
    public function actionRegister()
    {
        $data = Yii::$app->request->getBodyParams();
        
        $device = $this->pushService->registerDevice(
            Yii::$app->user->id,
            $data['client_id'],
            $data['device_id'],
            $data['push_token'] ?? null,
            $data['platform'] ?? null
        );

        return [
            'success' => true,
            'device' => $device->attributes,
        ];
    }

    /**
     * @OA\Put(
     *     path="/api/v1/push-services/token",
     *     tags={"push-services"},
     *     summary="Обновление push-токена",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"device_id","push_token"},
     *             @OA\Property(property="device_id", type="string"),
     *             @OA\Property(property="push_token", type="string")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Токен обновлен")
     * )
     */
    public function actionUpdatePushToken()
    {
        $data = Yii::$app->request->getBodyParams();
        
        $device = $this->pushService->updatePushToken(
            $data['device_id'],
            $data['push_token']
        );

        return [
            'success' => true,
            'device' => $device->attributes,
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/v1/push-services/devices",
     *     tags={"push-services"},
     *     summary="Получение устройств пользователя",
     *     @OA\Response(
     *         response="200",
     *         description="Список устройств"
     *     )
     * )
     */
    public function actionGetDevices()
    {
        $devices = $this->pushService->getUserDevices(Yii::$app->user->id);
        
        return [
            'success' => true,
            'devices' => $devices,
        ];
    }
} 