<?php

namespace app\controllers\api\v1\client;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\controllers\api\v1\ClientController;
use app\helpers\POSTHelper;
use app\models\Category;
use app\models\User;
use app\models\UserSettings;
use app\services\output\SettingsOutputService;
use Throwable;
use Yii;

class SettingsController extends ClientController
{
    public function behaviors()
    {
        $behaviours = parent::behaviors();

        $behaviours['verbFilter']['actions']['self'] = ['get'];
        $behaviours['verbFilter']['actions']['update'] = ['put'];
        $behaviours['verbFilter']['actions']['set-categories'] = ['put'];
        array_unshift($behaviours['access']['rules'], [
            'actions' => ['update', 'delete'],
            'allow' => false,
            'matchCallback' => fn() => User::getIdentity()->role === User::ROLE_CLIENT_DEMO,
        ]);
        $behaviours['access']['denyCallback'] = static function () {
            $response =
                User::getIdentity()->role === User::ROLE_CLIENT_DEMO ?
                ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_AUTHENTICATED) :
                false;
            Yii::$app->response->data = $response;
        };

        return $behaviours;
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
                'enable_notifications',
                'currency',
                'application_language',
                'chat_language',
            ]);

            $settings->load($postParams, '');

            if (!$settings->save()) {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $settings->getFirstErrors(),
                );
            }

            return ApiResponse::info(
                SettingsOutputService::getEntity($user->id),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
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
