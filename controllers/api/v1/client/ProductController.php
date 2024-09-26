<?php

namespace app\controllers\api\v1\client;

use app\components\ApiResponse;
use app\controllers\api\v1\ClientController;
use app\models\Product;
use app\services\output\ProductOutputService;

class ProductController extends ClientController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['view'] = ['get'];

        return $behaviors;
    }

    public function actionView(int $id)
    {
        $apiCodes = Product::apiCodes();
        $isset = Product::isset(['id' => $id]);

        if (!$isset) {
            return ApiResponse::byResponseCode($apiCodes->NOT_FOUND);
        }

        return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
            'info' => ProductOutputService::getEntity(
                $id,
                'small'
            ),
        ]);
    }
}
