<?php

namespace app\controllers\api\v1\buyer\feedback;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\models\User;
use Yii;
use app\models\FeedbackBuyer;
use app\services\output\FeedbackBuyerOutputService;

class BuyerController extends \app\controllers\api\v1\BuyerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['collection'] = ['get'];
        array_unshift($behaviors['access']['rules'], [
            'actions' => ['collection'],
            'allow' => false,
            'matchCallback' => fn() => User::getIdentity()->is([
                User::ROLE_BUYER_DEMO
            ]),
        ]);
        $behaviors['access']['denyCallback'] = static function () {
            $response =
                User::getIdentity()->is([
                    User::ROLE_BUYER_DEMO
                ]) ?
                ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_AUTHENTICATED) :
                false;
            Yii::$app->response->data = $response;
        };

        return $behaviors;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/buyer/feedback/buyer/collection/{id}",
     *     summary="Получить коллекцию отзывов для покупателя",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID покупателя",
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
     *         description="Покупатель не найден"
     *     )
     * )
     */
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
