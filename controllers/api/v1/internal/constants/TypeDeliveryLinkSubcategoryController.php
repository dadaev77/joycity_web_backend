<?php

namespace app\controllers\api\v1\internal\constants;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\helpers\POSTHelper;
use app\models\TypeDeliveryLinkSubcategory;
use app\services\output\TypeDeliveryLinkSubcategoryOutputService;
use app\services\SaveModelService;
use Throwable;

class TypeDeliveryLinkSubcategoryController extends InternalController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];
        $behaviors['verbFilter']['actions']['create'] = ['post'];
        $behaviors['verbFilter']['actions']['delete'] = ['delete'];

        return $behaviors;
    }

    public function actionIndex(
        $type_delivery_id = null,
        $subcategory_id = null,
    ) {
        try {
            $apiCodes = TypeDeliveryLinkSubcategory::apiCodes();

            if (!$type_delivery_id && !$subcategory_id) {
                return ApiResponse::codeErrors($apiCodes->BAD_REQUEST, [
                    'type_delivery_id' => 'Param `type_delivery_id` is empty',
                    'subcategory_id' => 'Param `subcategory_id` is empty',
                ]);
            }

            $query = TypeDeliveryLinkSubcategory::find();

            if ($type_delivery_id) {
                $query->andWhere(['type_delivery_id' => $type_delivery_id]);
            }

            if ($subcategory_id) {
                $query->andWhere(['category_id' => $subcategory_id]);
            }

            return ApiResponse::collection(
                TypeDeliveryLinkSubcategoryOutputService::getCollection(
                    $query->column(),
                ),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }

    public function actionCreate()
    {
        try {
            $params = POSTHelper::getPostWithKeys(
                ['type_delivery_id', 'subcategory_id'],
                true,
            );

            $existLink = TypeDeliveryLinkSubcategory::find()
                ->select(['id'])
                ->where([
                    'type_delivery_id' => $params['type_delivery_id'],
                    'subcategory_id' => $params['subcategory_id'],
                ])
                ->one();

            if ($existLink) {
                return ApiResponse::info(
                    TypeDeliveryLinkSubcategoryOutputService::getEntity(
                        $existLink->id,
                    ),
                );
            }

            $newLink = new TypeDeliveryLinkSubcategory([
                'type_delivery_id' => $params['type_delivery_id'],
                'subcategory_id' => $params['subcategory_id'],
            ]);
            $newLinkSave = SaveModelService::validateAndSave($newLink);

            if ($newLinkSave->success) {
                return $newLinkSave->apiResponse;
            }

            return ApiResponse::info(
                TypeDeliveryLinkSubcategoryOutputService::getEntity(
                    $newLinkSave->model->id,
                ),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }

    public function actionDelete(int $id)
    {
        try {
            $apiCodes = TypeDeliveryLinkSubcategory::apiCodes();
            $link = TypeDeliveryLinkSubcategory::findOne(['id' => $id]);

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
