<?php

namespace app\controllers\api\v1\buyer\feedback;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\models\User;
use Yii;
use app\models\FeedbackProduct;
use app\services\output\FeedbackProductOutputService;

class ProductController extends \app\controllers\api\v1\BuyerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['collection'] = ['get'];
        array_unshift($behaviors['access']['rules'], [
            'actions' => ['collection'],
            'allow' => true,
            'matchCallback' => fn() => User::getIdentity()->role === User::ROLE_BUYER_DEMO,
        ]);
        $behaviors['access']['denyCallback'] = static function () {
            $response =
                User::getIdentity()->role === User::ROLE_BUYER_DEMO ?
                ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_AUTHENTICATED) :
                false;
            Yii::$app->response->data = $response;
        };

        return $behaviors;
    }

    public function actionCollection(int $id, int $offset = 0)
    {
        $apiCodes = FeedbackProduct::apiCodes();
        $collection = FeedbackProduct::find()
            ->select('id')
            ->where(['product_id' => $id])
            ->offset($offset)
            ->limit(10)
            ->column();
        $count = FeedbackProduct::find()
            ->where(['product_id' => $id])
            ->count();

        return ApiResponse::code($apiCodes->SUCCESS, [
            'count' => $count,
            'collection' => FeedbackProductOutputService::getCollection(
                $collection,
                'small'
            ),
        ]);
    }
}
