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

    /**
     * @OA\Get(
     *     path="/api/v1/internal/constants/rate/",
     *     tags={"Rate"},
     *     summary="Get list of rates",
     *     @OA\Response(response="200", description="Successful response")
     * )
     */
    public function actionIndex()
    {
        $rate = Rate::find();

        return ApiResponse::collection(
            RateOutputService::getCollection($rate->column()),
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/internal/constants/rate/create",
     *     tags={"Rate"},
     *     summary="Create a new rate",
     *     @OA\Response(response="200", description="Rate created successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     *     @OA\Response(response="500", description="Internal server error")
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/v1/internal/constants/rate/update/{id}",
     *     tags={"Rate"},
     *     summary="Update an existing rate",
     *     @OA\Parameter(name="id", in="path", required=true, description="Rate ID", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Rate updated successfully"),
     *     @OA\Response(response="404", description="Rate not found"),
     *     @OA\Response(response="400", description="Validation error"),
     *     @OA\Response(response="500", description="Internal server error")
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/v1/internal/constants/rate/delete/{id}",
     *     tags={"Rate"},
     *     summary="Delete a rate",
     *     @OA\Parameter(name="id", in="path", required=true, description="Rate ID", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Rate deleted successfully"),
     *     @OA\Response(response="404", description="Rate not found"),
     *     @OA\Response(response="500", description="Internal server error")
     * )
     */
    public function actionDelete(int $id)
    {
        $apiCodes = Rate::apiCodes();
        $rate = Rate::findOne(['id' => $id]);

        if (!$rate) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        $rate->delete();

        return ApiResponse::code($apiCodes->SUCCESS);
    }
}
