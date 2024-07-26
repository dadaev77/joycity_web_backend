<?php

namespace app\controllers;

use app\models\Order;
use app\models\User;
use app\models\Category;
use app\models\Subcategory;
use yii\web\Controller;
use app\components\ApiResponse;
use Yii;

class RawController extends Controller
{
    /**
     * This function retrieves all orders from the database and returns them as a collection.
     *
     * @return array An array of Order objects.
     *
     * @throws \Exception If there is an error retrieving data from the database.
     */
    public function actionIndex()
    {
        $order = Order::find()->all();
        return ApiResponse::collection($order);
    }

    /**
     * This function retrieves a list of buyers based on their role and sorts them by rating in descending order.
     * It includes the buyer's ID, rating, associated categories, and user settings.
     *
     * @return array An array of associative arrays representing buyers. Each buyer array contains 'id', 'rating',
     * 'categories', and 'userSettings' keys.
     *
     * @throws \Exception If there is an error retrieving data from the database.
     */
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
            ->asArray()
            ->all();

        return ApiResponse::collection($buyerIds);
    }
    /**
     * This function retrieves a list of categories and their corresponding subcategories.
     *
     * @return array An array of associative arrays, each containing a 'category' object and an array of 'subcategory' objects.
     *
     * @throws \Exception If there is an error retrieving data from the database.
     */
    public function actionCategoryAndSubcategory()
    {
        $res = [];
        $categories = Category::find()->all();
        foreach ($categories as $category) {
            $res[] = [
                'category' => $category,
                'subcategory' => Subcategory::find()->where(['category_id' => $category->id])->all(),
            ];
        }
        return ApiResponse::collection($res);
    }
    public function actionGeneratePassword($password)
    {
        return Yii::$app
            ->getSecurity()
            ->generatePasswordHash($password);
    }
}
