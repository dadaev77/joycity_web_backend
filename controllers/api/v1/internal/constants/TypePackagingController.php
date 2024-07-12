<?php

namespace app\controllers\api\v1\internal\constants;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\models\TypePackaging;
use app\services\output\TypePackagingOutputService;
use app\services\SaveModelService;
use Throwable;

class TypePackagingController extends InternalController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];
        $behaviors['verbFilter']['actions']['create'] = ['post'];
        $behaviors['verbFilter']['actions']['update'] = ['put'];

        return $behaviors;
    }

    public function actionIndex()
    {
        try {
            return ApiResponse::collection(
                TypePackagingOutputService::getCollection(
                    TypePackaging::find()->column(),
                ),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }

    public function actionCreate()
    {
        try {
            $typePackagingSave = SaveModelService::loadValidateAndSave(
                new TypePackaging(),
            );

            if (!$typePackagingSave->success) {
                return $typePackagingSave->apiResponse;
            }

            return ApiResponse::info(
                TypePackagingOutputService::getEntity(
                    $typePackagingSave->model->id,
                ),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }

    public function actionUpdate(int $id)
    {
        try {
            $typePackaging = TypePackaging::findOne(['id' => $id]);

            if (!$typePackaging) {
                return ApiResponse::code(TypePackaging::apiCodes()->NOT_FOUND);
            }

            $typePackagingSave = SaveModelService::loadValidateAndSave(
                $typePackaging,
            );

            if (!$typePackagingSave->success) {
                return $typePackagingSave->apiResponse;
            }

            return ApiResponse::info(
                TypePackagingOutputService::getEntity($typePackaging->id),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }
}
