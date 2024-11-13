<?php

namespace app\controllers\api\v1\internal\constants;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\models\Category;
use app\services\AttachmentService;
use app\services\output\CategoryOutputService;
use Throwable;
use Yii;
use yii\web\UploadedFile;

class CategoryController extends InternalController
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
     *     path="/api/v1/internal/constants/category",
     *     tags={"Category"},
     *     summary="Get list of categories",
     *     @OA\Response(response="200", description="Successful response")
     * )
     */
    public function actionIndex()
    {
        $categoryIds = Category::find();

        return ApiResponse::collection(
            CategoryOutputService::getCollection($categoryIds->column()),
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/internal/constants/category",
     *     tags={"Category"},
     *     summary="Create a new category",
     *     @OA\Response(response="200", description="Category created successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     *     @OA\Response(response="500", description="Internal server error")
     * )
     */
    public function actionCreate()
    {
        $apiCodes = Category::apiCodes();
        $request = Yii::$app->request;
        $image = UploadedFile::getInstancesByName('image');

        $transaction = Yii::$app->db->beginTransaction();

        if (!$image) {
            return ApiResponse::codeErrors($apiCodes->NOT_VALID, [
                'images' => 'Param `image` is empty',
            ]);
        }

        try {
            $category = new Category();
            $category->load($request->post(), '');

            if (!$category->validate()) {
                return ApiResponse::codeErrors(
                    $apiCodes->NOT_VALID,
                    $category->getFirstErrors(),
                );
            }

            $attachmentSaveResponse = AttachmentService::writeFilesCollection(
                $image,
                1,
                0,
            );

            if (!$attachmentSaveResponse->success) {
                $transaction?->rollBack();

                return ApiResponse::code($apiCodes->INTERNAL_ERROR, [
                    'images' => 'Failed to save images',
                ]);
            }

            $category->avatar_id = $attachmentSaveResponse->result[0]->id;

            if (!$category->save()) {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $category->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                CategoryOutputService::getEntity($category->id),
            );
        } catch (Throwable $e) {
            $transaction?->rollBack();
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/internal/constants/category/{id}",
     *     tags={"Category"},
     *     summary="Update an existing category",
     *     @OA\Parameter(name="id", in="path", required=true, description="Category ID", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Category updated successfully"),
     *     @OA\Response(response="404", description="Category not found"),
     *     @OA\Response(response="400", description="Validation error"),
     *     @OA\Response(response="500", description="Internal server error")
     * )
     */
    public function actionUpdate(int $id)
    {
        $apiCodes = Category::apiCodes();
        $request = Yii::$app->request;
        $category = Category::findOne(['id' => $id]);
        $postParams = array_intersect_key(
            $request->post(),
            array_flip(['zh_name', 'ru_name', 'en_name']),
        );
        $transaction = null;

        if (!$category) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        try {
            $transaction = Yii::$app->db->beginTransaction();

            $category->load($postParams, '');

            if (!$category->validate()) {
                return ApiResponse::codeErrors(
                    $apiCodes->NOT_VALID,
                    $category->getFirstErrors(),
                );
            }

            $image = UploadedFile::getInstancesByName('image');
            if ($image) {
                $attachmentSaveResponse = AttachmentService::writeFilesCollection(
                    $image,
                    1,
                    0,
                );

                if (!$attachmentSaveResponse->success) {
                    $transaction?->rollBack();

                    return ApiResponse::code($apiCodes->INTERNAL_ERROR, [
                        'images' => 'Failed to save images',
                    ]);
                }
                $category->avatar_id = $attachmentSaveResponse->result[0]->id;
            }
            if (!$category->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $category->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                CategoryOutputService::getEntity($category->id),
            );
        } catch (Throwable $e) {
            $transaction?->rollBack();
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/internal/constants/category/{id}",
     *     tags={"Category"},
     *     summary="Delete a category",
     *     @OA\Parameter(name="id", in="path", required=true, description="Category ID", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Category deleted successfully"),
     *     @OA\Response(response="404", description="Category not found"),
     *     @OA\Response(response="500", description="Internal server error")
     * )
     */
    public function actionDelete(int $id)
    {
        $apiCodes = Category::apiCodes();
        $category = Category::findOne(['id' => $id]);
        $transaction = null;

        if (!$category) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }
        try {
            $transaction = Yii::$app->db->beginTransaction();

            $category->is_deleted = 1;

            if (!$category->save()) {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $category->getFirstErrors(),
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
