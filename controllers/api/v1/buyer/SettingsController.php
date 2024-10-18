<?php

namespace app\controllers\api\v1\buyer;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\controllers\api\v1\BuyerController;
use app\models\Category;
use app\models\TypeDelivery;
use app\models\TypePackaging;
use app\models\User;
use app\models\UserSettings;
use app\services\output\SettingsOutputService;
use app\services\SaveModelService;
use Throwable;
use Yii;

class SettingsController extends BuyerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['self'] = ['get'];
        $behaviors['verbFilter']['actions']['update'] = ['put'];
        $behaviors['verbFilter']['actions']['set-packaging'] = ['put'];
        $behaviors['verbFilter']['actions']['set-delivery'] = ['put'];
        $behaviors['verbFilter']['actions']['set-categories'] = ['put'];
        array_unshift($behaviors['access']['rules'], [
            'actions' => ['*'],
            'allow' => true,
            'matchCallback' => fn() => User::getIdentity()->role === User::ROLE_BUYER_DEMO,
        ]);
        $behaviors['access']['denyCallback'] = static function () {
            $response =
                User::getIdentity()->role === User::ROLE_BUYER_DEMO ?
                ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_AUTHENTICATED) :
                false;
            Yii::$app->response->data = $response;
        };
        return $behaviors;
    }

    public function actionSelf()
    {
        $user = User::getIdentity();

        return ApiResponse::info(SettingsOutputService::getEntity($user->id));
    }

    public function actionUpdate()
    {
        try {
            $user = User::getIdentity();
            $userSettingsSave = SaveModelService::loadValidateAndSave(
                $user->userSettings,
                [
                    'enable_notifications',
                    'currency',
                    'application_language',
                    'chat_language',
                    'selected_categories',
                ],
            );

            if (!$userSettingsSave->success) {
                return $userSettingsSave->apiResponse;
            }

            return ApiResponse::info(
                SettingsOutputService::getEntity($user->id),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }

    public function actionSetPackaging()
    {
        $request = Yii::$app->request;
        $apiCodes = User::apiCodes();

        $packagingIds = $request->post('packaging_ids');

        if (empty($packagingIds)) {
            return ApiResponse::byResponseCode($apiCodes->BAD_REQUEST, [
                'errors' => [
                    'packaging_type' => 'Тип упаковки не выбран',
                ],
            ]);
        }

        $user = User::getIdentity();
        $user->unlinkAll('packaging', true);

        foreach ($packagingIds as $packagingId) {
            $packaging = TypePackaging::findOne($packagingId);
            if (empty($packaging)) {
                return ApiResponse::byResponseCode($apiCodes->BAD_REQUEST, [
                    'errors' => [
                        'packaging_ids' =>
                        'Одного из типа упаковки не существует',
                    ],
                ]);
            }
            $user->link('packaging', $packaging);
        }

        return ApiResponse::byResponseCode(null, [
            'info' => SettingsOutputService::getEntity($user->id),
        ]);
    }

    public function actionSetDelivery()
    {
        $request = Yii::$app->request;
        $apiCodes = User::apiCodes();

        $deliveryIds = $request->post('delivery_ids');

        if (empty($deliveryIds)) {
            return ApiResponse::byResponseCode($apiCodes->BAD_REQUEST, [
                'errors' => [
                    'delivery_type' => 'Типа доставки не выбран',
                ],
            ]);
        }

        $user = User::getIdentity();
        $user->unlinkAll('delivery', true);

        foreach ($deliveryIds as $deliveryId) {
            $delivery = TypeDelivery::findOne($deliveryId);
            if (empty($delivery)) {
                return ApiResponse::byResponseCode($apiCodes->BAD_REQUEST, [
                    'errors' => [
                        'delivery_ids' =>
                        'Одного из типа доставки не существует',
                    ],
                ]);
            }
            $user->link('delivery', $delivery);
        }

        return ApiResponse::byResponseCode(null, [
            'info' => SettingsOutputService::getEntity($user->id),
        ]);
    }

    public function actionSetCategories()
    {
        $apiCodes = UserSettings::apiCodes();

        try {
            $user = User::getIdentity();
            $request = Yii::$app->request;
            $categoryIds = $request->post('category_ids');

            if (!$categoryIds) {
                return ApiResponse::codeErrors($apiCodes->BAD_REQUEST, [
                    'category_ids' => 'Param `category_ids` is empty',
                ]);
            }

            $user->linkAll(
                'categories',
                Category::findAll(['id' => $categoryIds]),
            );

            return ApiResponse::info(
                SettingsOutputService::getEntity($user->id),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }
}
