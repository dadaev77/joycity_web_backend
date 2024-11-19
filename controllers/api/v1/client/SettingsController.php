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

    /**
     * @OA\Get(
     *     path="/api/v1/client/settings/self",
     *     summary="Получить настройки текущего пользователя",
     *     description="Возвращает настройки текущего пользователя.",
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ с настройками пользователя",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", example={"enable_notifications": true, "currency": "USD", "application_language": "en", "chat_language": "en"})
     *         )
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
     *     path="/api/v1/client/settings/update",
     *     summary="Обновить настройки пользователя",
     *     description="Обновляет настройки текущего пользователя на основе переданных данных.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"enable_notifications", "currency", "application_language", "chat_language"},
     *             @OA\Property(property="enable_notifications", type="boolean"),
     *             @OA\Property(property="currency", type="string"),
     *             @OA\Property(property="application_language", type="string"),
     *             @OA\Property(property="chat_language", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешное обновление настроек пользователя",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", example={"enable_notifications": true, "currency": "USD", "application_language": "en", "chat_language": "en"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Некорректные данные"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера"
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

    /**
     * @OA\Put(
     *     path="/api/v1/client/settings/set-categories",
     *     summary="Установить категории для пользователя",
     *     description="Устанавливает категории для текущего пользователя на основе переданных идентификаторов.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"category_ids"},
     *             @OA\Property(property="category_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешное обновление категорий пользователя",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", 
     *                 @OA\Property(property="categories", type="array", @OA\Items(type="integer"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Некорректные данные"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера"
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
