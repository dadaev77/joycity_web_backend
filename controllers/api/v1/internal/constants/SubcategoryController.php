<?php

namespace app\controllers\api\v1\internal\constants;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\models\Subcategory;
use app\services\output\SubcategoryOutputService;
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

    public function actionIndex()
    {
        $subCategoryIds = Subcategory::find();

        return ApiResponse::collection(
            SubcategoryOutputService::getCollection($subCategoryIds->column()),
        );
    }

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
