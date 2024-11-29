<?php

namespace app\controllers\api\v1\internal\constants;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\models\Rate;
use app\services\output\RateOutputService;
use app\services\SaveModelService;
use Throwable;

class RateController extends InternalController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['create'] = ['post'];
        $behaviors['verbFilter']['actions']['update'] = ['put'];
        $behaviors['verbFilter']['actions']['index'] = ['get'];

        return $behaviors;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/internal/constants/rate/",
     *     tags={"Rate"},
     *     summary="Получить список курсов валют",
     *     @OA\Response(response="200", description="Успешный ответ")
     * )
     */
    public function actionIndex()
    {
        $rate = Rate::find();

        return ApiResponse::collection(
            RateOutputService::getCollection($rate->column()),
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/internal/constants/rate/create",
     *     tags={"Rate"},
     *     summary="Создать новый курс валюты",
     *     @OA\Response(response="200", description="Курс валюты успешно создан"),
     *     @OA\Response(response="400", description="Ошибка валидации"),
     *     @OA\Response(response="500", description="Внутренняя ошибка сервера")
     * )
     */
    public function actionCreate()
    {
        $rate = new Rate();
        $rate->created_at = date('Y-m-d H:i:s');
        $rate->RUB = 1;
        $rateSave = SaveModelService::loadValidateAndSave(
            $rate,
            ['CNY', 'USD'],
            null,
            true,
        );

        if (!$rateSave->success) {
            return $rateSave->apiResponse;
        }
        return ApiResponse::info(RateOutputService::getEntity($rate->id));
    }

    /**
     * @OA\Put(
     *     path="/api/v1/internal/constants/rate/update/{id}",
     *     tags={"Rate"},
     *     summary="Обновить существующий курс валюты",
     *     @OA\Parameter(name="id", in="path", required=true, description="ID курса валюты", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Курс валюты успешно обновлен"),
     *     @OA\Response(response="404", description="Курс валюты не найден"),
     *     @OA\Response(response="400", description="Ошибка валидации"),
     *     @OA\Response(response="500", description="Внутренняя ошибка сервера")
     * )
     */
    public function actionUpdate(int $id)
    {
        $apiCodes = Rate::apiCodes();
        $rate = Rate::findOne(['id' => $id]);

        if (!$rate) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        // $rate->created_at = date('Y-m-d H:i:s');
        $rateSave = SaveModelService::loadValidateAndSave(
            $rate,
            ['RUB', 'USD'],
            null,
            true,
        );

        if (!$rateSave->success) {
            return $rateSave->apiResponse;
        }

        return ApiResponse::info(RateOutputService::getEntity($rate->id));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/internal/constants/rate/delete/{id}",
     *     tags={"Rate"},
     *     summary="Удалить курс валюты",
     *     @OA\Parameter(name="id", in="path", required=true, description="ID курса валюты", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Курс валюты успешно удален"),
     *     @OA\Response(response="404", description="Курс валюты не найден"),
     *     @OA\Response(response="500", description="Внутренняя ошибка сервера")
     * )
     */
    public function actionDelete(int $id)
    {
        $apiCodes = Rate::apiCodes();
        $rate = Rate::findOne(['id' => $id]);

        if (!$rate) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        $rate->delete();

        return ApiResponse::code($apiCodes->SUCCESS);
    }
}
