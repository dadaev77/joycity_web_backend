<?php

namespace app\controllers\api\v1\internal\constants;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\models\Rate;
use app\services\output\RateOutputService;
use app\services\SaveModelService;

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

    public function actionIndex()
    {
        $rate = Rate::find();

        return ApiResponse::collection(
            RateOutputService::getCollection($rate->column()),
        );
    }
}
