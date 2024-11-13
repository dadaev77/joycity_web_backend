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

    /**
     * @OA\Get(
     *     path="/api/v1/internal/constants/type-packaging",
     *     summary="Get collection of Type Packaging",
     *     @OA\Response(response="200", description="Successful response"),
     *     @OA\Response(response="500", description="Internal server error")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/internal/constants/type-packaging/create",
     *     summary="Create a new Type Packaging",
     *     @OA\Response(response="200", description="Successful creation"),
     *     @OA\Response(response="500", description="Internal server error")
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/v1/internal/constants/type-packaging/update/{id}",
     *     summary="Update a Type Packaging",
     *     @OA\Parameter(name="id", in="path", required=true, description="ID of the Type Packaging to update", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Successful update"),
     *     @OA\Response(response="404", description="Not found"),
     *     @OA\Response(response="500", description="Internal server error")
     * )
     */
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
