<?php

namespace app\controllers\api\v1\internal;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\helpers\POSTHelper;
use app\models\AppOption;
use app\services\OptionsService;
use app\services\output\OptionOutputService;

class OptionsController extends InternalController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];
        $behaviors['verbFilter']['actions']['view'] = ['get'];
        $behaviors['verbFilter']['actions']['update'] = ['put'];

        return $behaviors;
    }

    /**
     * @OA\Put(
     *     path="/api/v1/internal/options/update/{id}",
     *     summary="Обновить опцию",
     *     tags={"Options"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID опции",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="value", type="string", example="new value")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Опция успешно обновлена"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Опция не найдена"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации параметров"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера"
     *     )
     * )
     */
    public function actionUpdate(int $id)
    {
        $apiCodes = AppOption::apiCodes();
        $options = OptionsService::updateById(
            $id,
            ...POSTHelper::getPostWithKeys(['value']),
        );

        if ($options->isNotFound) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }
        if ($options->isNotValid) {
            return ApiResponse::codeErrors(
                $apiCodes->NOT_VALID,
                $options->reason,
            );
        }

        return ApiResponse::info(
            OptionOutputService::getEntity($options->result->id),
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/internal/options/view/{id}",
     *     summary="Просмотр опции",
     *     tags={"Options"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID опции",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Опция успешно найдена"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Опция не найдена"
     *     )
     * )
     */
    public function actionView(int $id)
    {
        $apiCodes = AppOption::apiCodes();
        $isset = AppOption::isset(['id' => $id]);

        if (!$isset) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        return ApiResponse::info(OptionOutputService::getEntity($id));
    }

    /**
     * @OA\Get(
     *     path="/api/v1/internal/options/index",
     *     summary="Получить список опций",
     *     tags={"Options"},
     *     @OA\Response(
     *         response=200,
     *         description="Список опций успешно получен"
     *     )
     * )
     */
    public function actionIndex()
    {
        $options = AppOption::find();

        return ApiResponse::collection(
            OptionOutputService::getCollection($options->column()),
        );
    }
}
