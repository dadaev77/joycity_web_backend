<?php

namespace app\controllers\api\v1\buyer\feedback;

use app\components\ApiResponse;
use app\models\FeedbackBuyer;
use app\services\output\FeedbackBuyerOutputService;

class BuyerController extends \app\controllers\api\v1\BuyerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['collection'] = ['get'];

        return $behaviors;
    }

    public function actionCollection(int $id, int $offset = 0)
    {
        $apiCodes = FeedbackBuyer::apiCodes();
        $collection = FeedbackBuyer::find()
            ->select('id')
            ->where(['buyer_id' => $id])
            ->offset($offset)
            ->limit(10)
            ->column();
        $count = FeedbackBuyer::find()
            ->where(['buyer_id' => $id])
            ->count();

        return ApiResponse::code($apiCodes->SUCCESS, [
            'count' => $count,
            'collection' => FeedbackBuyerOutputService::getCollection(
                $collection,
            ),
        ]);
    }
}
