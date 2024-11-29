<?php

namespace app\controllers\api\v1\internal;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\models\Order;
use app\services\output\OrderOutputService;
use Yii;

class OrderController extends InternalController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];
        $behaviors['verbFilter']['actions']['view'] = ['get'];

        return $behaviors;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/internal/order/index",
     *     summary="Получить список заказов",
     *     security={{"Bearer":{}}},
     *     tags={"Order"},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         required=false,
     *         description="Тип заказа (order/request)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=false,
     *         description="Поиск по ID заказа",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         required=false,
     *         description="Дата начала создания заказа",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         required=false,
     *         description="Дата окончания создания заказа",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Статус заказа",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="email_buyer",
     *         in="query",
     *         required=false,
     *         description="Email покупателя",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="email_client",
     *         in="query",
     *         required=false,
     *         description="Email клиента",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список заказов успешно получен"
     *     )
     * )
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $queryModel = Order::find()->select(['order.id']);

        if ($type = $request->get('type', 'order')) {
            $queryModel->andWhere([
                'IN',
                'order.status',
                $type === 'request'
                    ? Order::STATUS_GROUP_REQUEST
                    : Order::STATUS_GROUP_ORDER,
            ]);
        }

        if ($query = $request->get('query')) {
            $queryModel->andWhere(['LIKE', 'order.id', "%$query%", false]);
        }

        if ($from = $request->get('from')) {
            $queryModel->andWhere(['>=', 'order.created_at', $from]);
        }

        if ($to = $request->get('to')) {
            $queryModel->andWhere([
                '<=',
                'order.created_at',
                date('Y-m-d', strtotime($to . ' +1 day')),
            ]);
        }

        if ($status = $request->get('status')) {
            $queryModel->andWhere(['order.status' => $status]);
        }

        if ($emailBuyer = $request->get('email_buyer')) {
            $queryModel
                ->joinWith(['buyer' => fn($q) => $q->select(['id', 'email'])])
                ->andWhere(['LIKE', 'user.email', $emailBuyer]);
        }

        if ($emailClient = $request->get('email_client')) {
            $queryModel
                ->joinWith([
                    'createdBy' => fn($q) => $q->select(['id', 'email']),
                ])
                ->andWhere(['LIKE', 'user.email', $emailClient]);
        }

        return ApiResponse::collection(
            OrderOutputService::getCollection(
                $queryModel->column(),
                false, // Show deleted
                'small', // Size of output images
            ),
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/internal/order/view/{id}",
     *     summary="Просмотр заказа",
     *     security={{"Bearer":{}}},
     *     tags={"Order"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID заказа",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Заказ успешно найден"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заказ не найден"
     *     )
     * )
     */
    public function actionView(int $id)
    {
        $apiCodes = Order::apiCodes();
        $order = Order::findOne(['id' => $id]);

        if (!$order) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        return ApiResponse::info(
            OrderOutputService::getEntity(
                $id,
                false, // Show deleted
                'small', // Size of output images
            ),
        );
    }
}
