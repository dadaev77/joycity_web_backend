<?php

namespace app\controllers\api\v1\fulfillment;

use app\components\ApiResponse;
use app\controllers\api\v1\FulfillmentController;
use app\helpers\POSTHelper;
use app\models\Order;
use app\models\User;
use app\services\AttachmentService;
use app\services\output\ProfileOutputService;
use Throwable;
use Yii;
use yii\web\UploadedFile;

class ProfileController extends FulfillmentController
{
    public function behaviors()
    {
        $behaviours = parent::behaviors();
        $behaviours['verbFilter']['actions']['update'] = ['put'];
        $behaviours['verbFilter']['actions']['upload-avatar'] = ['post'];
        $behaviours['verbFilter']['actions']['self'] = ['get'];
        $behaviours['verbFilter']['actions']['delete'] = ['delete'];

        return $behaviours;
    }
    public function actionUploadAvatar()
    {
        $apiCodes = User::apiCodes();
        $user = Yii::$app->user->identity;
        $images = UploadedFile::getInstancesByName('images');
        $transaction = Yii::$app->db->beginTransaction();

        if (!$images) {
            $transaction?->rollBack();
            return ApiResponse::codeErrors($apiCodes->BAD_REQUEST, [
                'images' => 'Вы не выбрали картинку',
            ]);
        }

        try {
            $attachmentSaveResponse = AttachmentService::writeFilesCollection(
                $images,
                1,
                0,
            );

            if (!$attachmentSaveResponse->success) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors($apiCodes->INTERNAL_ERROR, [
                    'images' => 'Ошибка сохранения',
                ]);
            }
            $user->avatar_id = $attachmentSaveResponse->result[0]->id;
            $user->save(false);

            $transaction?->commit();

            return ApiResponse::info(
                ProfileOutputService::getEntity($user->id),
            );
        } catch (Throwable $e) {
            $transaction?->rollBack();

            return ApiResponse::internalError($e);
        }
    }

    public function actionSelf()
    {
        $userId = Yii::$app->user->identity->id;
        $apiCodes = User::apiCodes();
        $isset = User::isset(['id' => $userId]);

        if (!$isset) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        return ApiResponse::info(ProfileOutputService::getEntity($userId));
    }

    public function actionUpdate()
    {
        $apiCodes = User::apiCodes();
        $user = Yii::$app->user->identity;
        $postParams = POSTHelper::getPostWithKeys([
            'name',
            'surname',
            'phone_number',
            'organization_name',
            'phone_country_code',
            'address',
            'city',
            'country',
        ]);

        try {
            $transaction = Yii::$app->db->beginTransaction();

            if ($postParams['address']) {
                $deliveryPointAddress = $user->deliveryPointAddress;

                if (!$deliveryPointAddress) {
                    $transaction?->rollBack();
                    return ApiResponse::codeErrors($apiCodes->ERROR_SAVE, [
                        'address' => 'Linked address not found',
                    ]);
                }

                $deliveryPointAddress->address = $postParams['address'];

                if (!$deliveryPointAddress->save()) {
                    $transaction?->rollBack();
                    return ApiResponse::codeErrors(
                        $apiCodes->ERROR_SAVE,
                        $deliveryPointAddress->getFirstErrors(),
                    );
                }
            }

            $user->load($postParams, '');

            if (isset($postParams['phone_number'])) {
                $existingUser = User::isset([
                    'phone_number' => $user->phone_number,
                    'phone_country_code' => $user->phone_country_code,
                ]);

                if ($existingUser) {
                    return ApiResponse::code($apiCodes->PHONE_NUMBER_EXISTS);
                }

                if (isset($postParams['phone_country_code'])) {
                    $user->phone_country_code =
                        $postParams['phone_country_code'];
                }
            }

            if (!$user->save(true, array_keys($postParams))) {
                $transaction?->rollBack();
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $user->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                ProfileOutputService::getEntity($user->id),
            );
        } catch (Throwable $e) {
            $transaction?->rollBack();
            return ApiResponse::internalError($e);
        }
    }

    public function actionDelete()
    {
        try {
            $user = Yii::$app->user->identity;
            $apiCodes = User::apiCodes();

            $hasForbiddenOrders = Order::find()
                ->where(['fulfillment_id' => $user->id])
                ->andWhere([
                    'NOT IN',
                    'status',
                    [
                        Order::STATUS_COMPLETED,
                        Order::STATUS_CANCELLED_REQUEST,
                        Order::STATUS_CANCELLED_ORDER,
                    ],
                ])
                ->exists();

            if ($hasForbiddenOrders) {
                return ApiResponse::code($apiCodes->HAS_ACTIVE_ORDER);
            }

            $transaction = Yii::$app->db->beginTransaction();

            $user->is_deleted = 1;

            if (!$user->save(false)) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $user->getFirstErrors(),
                );
            }

            $userAddress = $user->deliveryPointAddress;
            $userAddress->is_deleted = 1;

            if (!$userAddress->save()) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $userAddress->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::code($apiCodes->SUCCESS);
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }
}
