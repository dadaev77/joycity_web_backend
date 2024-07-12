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

    public function actionSelf()
    {
        $user = User::getIdentity();

        return ApiResponse::info(SettingsOutputService::getEntity($user->id));
    }

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
