<?php

namespace app\controllers\api\v1\fulfillment;

use app\components\ApiResponse;
use app\controllers\api\v1\FulfillmentController;
use app\helpers\POSTHelper;
use app\models\User;
use app\services\output\SettingsOutputService;
use Throwable;
use Yii;

class SettingsController extends FulfillmentController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['self'] = ['get'];
        $behaviors['verbFilter']['actions']['update'] = ['put'];

        return $behaviors;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/fulfillment/settings/self",
     *     summary="Получить информацию о пользователе",
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Пользователь не найден"
     *     )
     * )
     */
    public function actionSelf()
    {
        $user = User::getIdentity();

        return ApiResponse::info(SettingsOutputService::getEntity($user->id));
    }

    /**
     * @OA\Put(
     *     path="/api/v1/fulfillment/settings/update",
     *     summary="Обновить настройки пользователя",
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Неверный запрос"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера"
     *     )
     * )
     */
    public function actionUpdate()
    {
        $apiCodes = User::apiCodes();

        try {
            $user = User::getIdentity();
            $settings = $user->userSettings;
            $postParams = POSTHelper::getPostWithKeys([
                'high_workload',
                'application_language',
                'chat_language',
            ]);
            $deliveryPointAddress = Yii::$app->request->post(
                'delivery_point_address',
            );

            $transaction = Yii::$app->db->beginTransaction();

            if ($deliveryPointAddress) {
                $userAddress = $user->deliveryPointAddress;
                $userAddress->address = $deliveryPointAddress;

                if (!$userAddress->save()) {
                    return ApiResponse::transactionCodeErrors(
                        $transaction,
                        $apiCodes->ERROR_SAVE,
                        $userAddress->getFirstErrors(),
                    );
                }
            }

            $settings->load($postParams, '');

            if (!$settings->save()) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $settings->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                SettingsOutputService::getEntity($user->id),
            );
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }
}
