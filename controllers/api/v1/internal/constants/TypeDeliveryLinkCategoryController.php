<?php

namespace app\controllers\api\v1\internal\constants;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\helpers\POSTHelper;
use app\models\TypeDeliveryLinkCategory;
use app\services\output\TypeDeliveryLinkCategoryOutputService;
use app\services\SaveModelService;
use Throwable;

class TypeDeliveryLinkCategoryController extends InternalController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];
        $behaviors['verbFilter']['actions']['create'] = ['post'];
        $behaviors['verbFilter']['actions']['delete'] = ['delete'];

        return $behaviors;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/internal/constants/type-delivery-link-category",
     *     tags={"TypeDeliveryLinkCategory"},
     *     summary="Get list of type delivery link categories",
     *     @OA\Response(response="200", description="Successful response")
     * )
     */
    public function actionIndex($type_delivery_id = null, $category_id = null)
    {
        try {
            $apiCodes = TypeDeliveryLinkCategory::apiCodes();

            if (!$type_delivery_id && !$category_id) {
                return ApiResponse::codeErrors($apiCodes->BAD_REQUEST, [
                    'type_delivery_id' => 'Param `type_delivery_id` is empty',
                    'category_id' => 'Param `category_id` is empty',
                ]);
            }

            $query = TypeDeliveryLinkCategory::find();

            if ($type_delivery_id) {
                $query->andWhere(['type_delivery_id' => $type_delivery_id]);
            }

            if ($category_id) {
                $query->andWhere(['category_id' => $category_id]);
            }

            return ApiResponse::collection(
                TypeDeliveryLinkCategoryOutputService::getCollection(
                    $query->column(),
                ),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/internal/constants/type-delivery-link-category/create",
     *     tags={"TypeDeliveryLinkCategory"},
     *     summary="Create a new type delivery link category",
     *     @OA\Response(response="200", description="Type delivery link category created successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     *     @OA\Response(response="500", description="Internal server error")
     * )
     */
    public function actionCreate()
    {
        try {
            $params = POSTHelper::getPostWithKeys(
                ['type_delivery_id', 'category_id'],
                true,
            );

            $existLink = TypeDeliveryLinkCategory::find()
                ->select(['id'])
                ->where([
                    'type_delivery_id' => $params['type_delivery_id'],
                    'category_id' => $params['category_id'],
                ])
                ->one();

            if ($existLink) {
                return ApiResponse::info(
                    TypeDeliveryLinkCategoryOutputService::getEntity(
                        $existLink->id,
                    ),
                );
            }

            $newLink = new TypeDeliveryLinkCategory([
                'type_delivery_id' => $params['type_delivery_id'],
                'category_id' => $params['category_id'],
            ]);
            $newLinkSave = SaveModelService::validateAndSave($newLink);

            if ($newLinkSave->success) {
                return $newLinkSave->apiResponse;
            }

            return ApiResponse::info(
                TypeDeliveryLinkCategoryOutputService::getEntity(
                    $newLinkSave->model->id,
                ),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }


    /**
     * @OA\Delete(
     *     path="/api/v1/internal/constants/type-delivery-link-category/delete/{id}",
     *     tags={"TypeDeliveryLinkCategory"},
     *     summary="Delete a type delivery link category",
     *     @OA\Parameter(name="id", in="path", required=true, description="Type Delivery Link Category ID", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Type delivery link category deleted successfully"),
     *     @OA\Response(response="404", description="Type delivery link category not found"),
     *     @OA\Response(response="500", description="Internal server error")
     * )
     */
    public function actionDelete(int $id)
    {
        try {
            $apiCodes = TypeDeliveryLinkCategory::apiCodes();
            $link = TypeDeliveryLinkCategory::findOne(['id' => $id]);

            if (!$link) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (!$link->delete()) {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_DELETE,
                    $link->getFirstErrors(),
                );
            }

            return ApiResponse::code($apiCodes->SUCCESS);
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }
}
