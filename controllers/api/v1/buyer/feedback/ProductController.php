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

    /**
     * @OA\Get(
     *     path="/api/v1/buyer/feedback/product/collection/{id}",
     *     summary="Получить коллекцию отзывов для продукта",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID продукта",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         required=false,
     *         description="Смещение для пагинации",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ с коллекцией отзывов",
     *         @OA\JsonContent(
     *             @OA\Property(property="count", type="integer", description="Общее количество отзывов"),
     *             @OA\Property(property="collection", type="array", @OA\Items(type="object"), description="Список отзывов")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Продукт не найден"
     *     )
     * )
     */
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
