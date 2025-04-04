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
            'matchCallback' => fn() => User::getIdentity()->is([
                User::ROLE_BUYER_DEMO
            ]),
        ]);
        $behaviors['access']['denyCallback'] = static function () {
            $response =
                User::getIdentity()->is([
                    User::ROLE_BUYER_DEMO
                ]) ?
                ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_AUTHENTICATED) :
                false;
            Yii::$app->response->data = $response;
        };
        return $behaviors;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/buyer/settings/self",
     *     summary="Получить информацию о текущем пользователе",
     *     @OA\Response(
     *         response=200,
     *         description="Информация о пользователе успешно получена."
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Пользователь не авторизован."
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
     *     path="/api/v1/buyer/settings/update",
     *     summary="Обновить настройки пользователя",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"enable_notifications", "currency", "application_language"},
     *             @OA\Property(property="enable_notifications", type="boolean", example=true),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="application_language", type="string", example="en"),
     *             @OA\Property(property="chat_language", type="string", example="en"),
     *             @OA\Property(property="selected_categories", type="array", @OA\Items(type="integer"), example={1, 2, 3})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Настройки успешно обновлены."
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера."
     *     )
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/v1/buyer/settings/set-packaging",
     *     summary="Установить упаковку",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"packaging_ids"},
     *             @OA\Property(property="packaging_ids", type="array", @OA\Items(type="integer"), example={1, 2, 3})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Упаковка успешно установлена."
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера."
     *     )
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/v1/buyer/settings/set-delivery",
     *     summary="Установить доставку",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"delivery_ids"},
     *             @OA\Property(property="delivery_ids", type="array", @OA\Items(type="integer"), example={1, 2, 3})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Доставка успешно установлена."
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера."
     *     )
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/v1/buyer/settings/set-categories",
     *     summary="Установить категории",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"category_ids"},
     *             @OA\Property(property="category_ids", type="array", @OA\Items(type="integer"), example={1, 2, 3})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Категории успешно установлены."
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера."
     *     )
     * )
     */
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
