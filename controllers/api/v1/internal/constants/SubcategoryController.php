<?php

namespace app\controllers\api\v1\internal\constants;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\models\Subcategory;
use app\services\output\SubcategoryOutputService;
use app\services\SaveModelService;
use Throwable;
use Yii;

class SubcategoryController extends InternalController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];
        $behaviors['verbFilter']['actions']['create'] = ['post'];
        $behaviors['verbFilter']['actions']['update'] = ['put'];
        $behaviors['verbFilter']['actions']['delete'] = ['delete'];

        return $behaviors;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/internal/constants/subcategory",
     *     tags={"Constants"},
     *     summary="Получить список подкатегорий",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="zh_name", type="string"),
     *                 @OA\Property(property="ru_name", type="string"),
     *                 @OA\Property(property="en_name", type="string"),
     *                 @OA\Property(property="category_id", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function actionIndex()
    {
        $subCategoryIds = Subcategory::find();

        return ApiResponse::collection(
            SubcategoryOutputService::getCollection($subCategoryIds->column()),
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/internal/constants/subcategory/create",
     *     tags={"Subcategory"},
     *     summary="Создать новую подкатегорию",
     *     @OA\Response(response="200", description="Подкатегория успешно создана"),
     *     @OA\Response(response="400", description="Ошибка валидации"),
     *     @OA\Response(response="500", description="Внутренняя ошибка сервера")
     * )
     */
    public function actionCreate()
    {
        $apiCodes = Subcategory::apiCodes();
        $request = Yii::$app->request;
        $postParams = array_intersect_key(
            $request->post(),
            array_flip(['zh_name', 'ru_name', 'en_name', 'category_id']),
        );

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $subCategory = new Subcategory();
            $subCategory->load($postParams, '');
            if (!$subCategory->validate()) {
                return ApiResponse::codeErrors(
                    $apiCodes->NOT_VALID,
                    $subCategory->getFirstErrors(),
                );
            }
            if (!$subCategory->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $subCategory->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                SubcategoryOutputService::getEntity($subCategory->id),
            );
        } catch (Throwable $e) {
            $transaction?->rollBack();
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/internal/constants/subcategory/update/{id}",
     *     tags={"Subcategory"},
     *     summary="Обновить существующую подкатегорию",
     *     @OA\Parameter(name="id", in="path", required=true, description="ID подкатегории", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Подкатегория успешно обновлена"),
     *     @OA\Response(response="404", description="Подкатегория не найдена"),
     *     @OA\Response(response="400", description="Ошибка валидации"),
     *     @OA\Response(response="500", description="Внутренняя ошибка сервера")
     * )
     */
    public function actionUpdate(int $id)
    {
        $apiCodes = Subcategory::apiCodes();
        $request = Yii::$app->request;
        $subCategory = Subcategory::findOne(['id' => $id]);
        $postParams = array_intersect_key(
            $request->post(),
            array_flip(['zh_name', 'ru_name', 'en_name', 'category_id']),
        );
        $transaction = null;

        if (!$subCategory) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        try {
            $transaction = Yii::$app->db->beginTransaction();

            $subCategory->load($postParams, '');

            if (!$subCategory->validate()) {
                return ApiResponse::codeErrors(
                    $apiCodes->NOT_VALID,
                    $subCategory->getFirstErrors(),
                );
            }

            if (!$subCategory->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $subCategory->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                SubcategoryOutputService::getEntity($subCategory->id),
            );
        } catch (Throwable $e) {
            $transaction?->rollBack();
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/internal/constants/subcategory/delete/{id}",
     *     tags={"Subcategory"},
     *     summary="Удалить подкатегорию",
     *     @OA\Parameter(name="id", in="path", required=true, description="ID подкатегории", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Подкатегория успешно удалена"),
     *     @OA\Response(response="404", description="Подкатегория не найдена"),
     *     @OA\Response(response="500", description="Внутренняя ошибка сервера")
     * )
     */
    public function actionDelete(int $id)
    {
        $apiCodes = Subcategory::apiCodes();
        $subCategory = Subcategory::findOne(['id' => $id]);
        $transaction = null;

        if (!$subCategory) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }
        try {
            $transaction = Yii::$app->db->beginTransaction();

            $subCategory->is_deleted = 1;

            if (!$subCategory->save()) {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $subCategory->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::code($apiCodes->SUCCESS);
        } catch (Throwable $e) {
            $transaction?->rollBack();
            return ApiResponse::internalError($e);
        }
    }
}
