<?php

namespace app\controllers;

use app\models\Order;
use app\models\User;
use yii\web\Controller;
use app\components\ApiResponse;

class RawController extends Controller
{
    public function actionIndex()
    {
        $order = Order::find()->all();
        return ApiResponse::collection($order);
    }

    public function actionBuyerList()
    {
        $buyerIds = User::find()
            ->select(['id', 'rating'])
            ->with([
                'categories',
                'userSettings' => fn ($q) => $q->select([
                    'id',
                    'user_id',
                    'use_only_selected_categories',
                ]),
            ])
            ->where(['role' => User::ROLE_BUYER])
            ->orderBy(['rating' => SORT_DESC])
            ->asArray() // Convert the result to an array
            ->all(); // Execute the query and get all results

        return ApiResponse::collection($buyerIds); // Return the response in the expected format
    }
}
