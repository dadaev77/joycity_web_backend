<?php

namespace app\controllers\api\v1\manager;

use app\controllers\api\v1\ManagerController;
use app\models\User;
use app\components\ApiResponse;
use app\helpers\POSTHelper;
use app\models\UserSettings;
use app\models\Category;
use app\models\Charges;
use Throwable;
use Yii;
use app\services\output\SettingsOutputService;


class SettingsController extends ManagerController
{
    public function behaviors()
    {
        $behaviours = parent::behaviors();

        $behaviours['verbFilter']['actions']['self'] = ['get'];
        $behaviours['verbFilter']['actions']['update'] = ['put'];
        $behaviours['verbFilter']['actions']['set-categories'] = ['put'];
        $behaviours['verbFilter']['actions']['charges'] = ['get'];
        $behaviours['verbFilter']['actions']['charges-update'] = ['put'];
        // array_unshift($behaviours['access']['rules'], [
        //     'actions' => ['update', 'delete'],
        //     'allow' => false,
        //     'matchCallback' => fn() => User::getIdentity()->role === User::ROLE_CLIENT_DEMO,
        // ]);
        // $behaviours['access']['denyCallback'] = static function () {
        //     $response =
        //         User::getIdentity()->role === User::ROLE_CLIENT_DEMO ?
        //         ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_AUTHENTICATED) :
        //         false;
        //     Yii::$app->response->data = $response;
        // };

        return $behaviours;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/manager/settings/self",
     *     security={{"Bearer":{}}},
     *     summary="Получить настройки текущего пользователя",
     *     description="Возвращает настройки текущего пользователя.",
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован"
     *     )
     * )
     */
    public function actionSelf()
    {
        $user = User::getIdentity();
        $settings = SettingsOutputService::getEntity($user->id);

        // Добавляем информацию о наценках
        $charges = Charges::getCurrentCharges();
        if ($charges) {
            $settings['charges'] = $charges;
        }

        return ApiResponse::info($settings);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/manager/settings/charges",
     *     security={{"Bearer":{}}},
     *     summary="Получить текущие значения наценок на валюты",
     *     description="Возвращает текущие значения наценок USD и CNY.",
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ"
     *     )
     * )
     */
    public function actionCharges()
    {
        try {
            Yii::debug('Начало выполнения actionCharges');

            // Проверяем существование таблицы и записей
            $connection = Yii::$app->db;
            $tableExists = $connection->createCommand("SHOW TABLES LIKE 'charges'")->queryOne();

            if (!$tableExists) {
                Yii::error('Таблица charges не существует');
                return ApiResponse::internalError('Таблица charges не существует');
            }

            // Проверяем наличие записей
            $count = Charges::find()->count();
            Yii::debug('Количество записей в таблице charges: ' . $count);

            // Получаем данные через сервис
            $charges = \app\services\ChargesService::getCurrentCharges();
            Yii::debug('Полученные данные: ' . print_r($charges, true));

            return ApiResponse::info([
                'data' => $charges
            ]);
        } catch (\Throwable $e) {
            Yii::error('Ошибка при получении наценок: ' . $e->getMessage());
            Yii::error('Stack trace: ' . $e->getTraceAsString());

            return ApiResponse::internalError(
                YII_DEBUG ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : 'Ошибка при получении наценок'
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/manager/settings/charges/update",
     *     security={{"Bearer":{}}},
     *     summary="Обновить значения наценок на валюты",
     *     description="Обновляет значения наценок USD и CNY.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="usd_charge", type="integer"),
     *             @OA\Property(property="cny_charge", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешное обновление"
     *     )
     * )
     */
    public function actionChargesUpdate()
    {
        try {
            $charges = Charges::find()->one();
            if (!$charges) {
                $charges = new Charges();
            }

            $postParams = POSTHelper::getPostWithKeys([
                'usd_charge',
                'cny_charge',
            ]);

            $charges->load($postParams, '');

            if (!$charges->save()) {
                return ApiResponse::codeErrors(
                    'ERROR_SAVE',
                    $charges->getFirstErrors()
                );
            }

            return ApiResponse::info([
                'message' => 'Настройки наценок обновлены',
                'data' => [
                    'usd_charge' => $charges->usd_charge,
                    'cny_charge' => $charges->cny_charge,
                ]
            ]);
        } catch (Throwable $e) {
            return ApiResponse::internalError($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/manager/settings/update",
     *     security={{"Bearer":{}}},
     *     summary="Обновить настройки пользователя",
     *     description="Обновляет настройки текущего пользователя.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="enable_notifications", type="boolean"),
     *             @OA\Property(property="currency", type="string"),
     *             @OA\Property(property="application_language", type="string"),
     *             @OA\Property(property="chat_language", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации"
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
     *     path="/api/v1/manager/settings/set-categories",
     *     security={{"Bearer":{}}},
     *     summary="Установить категории для пользователя",
     *     description="Устанавливает категории для текущего пользователя.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="category_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера"
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
