<?php

namespace app\controllers\api\v1\buyer;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\controllers\api\v1\BuyerController;
use app\models\Category;
use app\models\Product;
use app\models\Subcategory;
use app\models\User;
use app\services\output\ProductOutputService;
use app\services\RateService;
use Yii;
use yii\helpers\Inflector;

class SearchController extends BuyerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['hints'] = ['get'];
        $behaviors['verbFilter']['actions']['search'] = ['get'];
        $behaviors['verbFilter']['actions']['popular'] = ['get'];
        $behaviors['verbFilter']['actions']['random'] = ['get'];

        return $behaviors;
    }

    public function actionHints()
    {
        $apiCodes = ResponseCodes::getStatic();
        $query = Yii::$app->request->get('query');

        if (!$query) {
            return ApiResponse::byResponseCode($apiCodes->BAD_REQUEST, [
                'errors' => ['query' => 'Param `query` is not valid'],
            ]);
        }

        $categories = Category::find()
            ->where(['like', 'en_name', $query . '%', false])
            ->orWhere(['like', 'ru_name', $query . '%', false])
            ->orWhere(['like', 'zh_name', $query . '%', false])
            ->limit(5)
            ->asArray()
            ->all();

        if ($categories) {
            return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
                'collection' => $categories,
                'type' => 'category',
            ]);
        }

        $subcategories = Subcategory::find()
            ->where(['like', 'en_name', $query . '%', false])
            ->orWhere(['like', 'ru_name', $query . '%', false])
            ->orWhere(['like', 'zh_name', $query . '%', false])
            ->limit(5)
            ->asArray()
            ->all();

        if ($subcategories) {
            return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
                'collection' => $subcategories,
                'type' => 'subcategory',
            ]);
        }

        $collection = Product::find()
            ->select(['id', 'name', 'subcategory_id'])
            ->where(['like', 'name', $query . '%', false])
            ->limit(5)
            ->asArray()
            ->all();

        if ($collection) {
            return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
                'collection' => $collection,
                'type' => 'product',
            ]);
        }

        return ApiResponse::byResponseCode($apiCodes->NOT_FOUND, [
            'collection' => [],
            'type' => null,
        ]);
    }

    public function actionSearch()
    {
        $apiCodes = ResponseCodes::getStatic();
        $request = Yii::$app->request;
        $user = User::getIdentity();
        $offset = $request->get('offset', 0);
        $sort = $request->get('sort');
        $priceMin = (float) $request->get('price_min');
        $priceMax = (float) $request->get('price_max');
        $queryString = trim($request->get('query', ''));

        $requiredParams = [
            'subcategoryId' => $request->get('subcategory_id'),
        ];
        $notValidParams = array_filter(
            $requiredParams,
            static fn($v) => empty($v),
        );

        extract($requiredParams);

        if ($notValidParams) {
            return ApiResponse::byResponseCode(
                $apiCodes->BAD_REQUEST,
                [
                    'errors' => array_reduce(
                        array_keys($notValidParams),
                        static fn($out, $key) => $out + [
                            Inflector::underscore(
                                $key,
                            ) => "Param `$key` is required",
                        ],
                        [],
                    ),
                ],
                400,
            );
        }

        if ($priceMin && $priceMax && $priceMax < $priceMin) {
            return ApiResponse::byResponseCode($apiCodes->BAD_REQUEST, [
                'errors' => ['price_max' => 'Param price_max is not valid'],
            ]);
        }

        if ($priceMin || $priceMax) {
            $priceMin = $priceMin
                ? RateService::putInUserCurrency($priceMin)
                : $priceMin;
            $priceMax = $priceMax
                ? RateService::putInUserCurrency($priceMax)
                : $priceMax;
        }

        $priceMinSql = 'LEAST(
            COALESCE(NULLIF(range_1_price, 0), 999999999),
            COALESCE(NULLIF(range_2_price, 0), 999999999),
            COALESCE(NULLIF(range_3_price, 0), 999999999),
            COALESCE(NULLIF(range_4_price, 0), 999999999)
        )';
        $priceMaxSql = 'GREATEST(
            COALESCE(NULLIF(range_1_price, 0), 0),
            COALESCE(NULLIF(range_2_price, 0), 0),
            COALESCE(NULLIF(range_3_price, 0), 0),
            COALESCE(NULLIF(range_4_price, 0), 0)
        )';

        $query = Product::find()
            ->select([
                'product.*',
                'price_min' => $priceMinSql,
                'price_max' => $priceMaxSql,
            ])
            ->where(['product.subcategory_id' => $subcategoryId])
            ->offset($offset)
            ->limit(20);

        if ($queryString) {
            $query->andWhere(['like', 'product.name', "%$queryString%", false]);
        }

        if ($priceMin && $priceMax) {
            $query->andWhere([
                'OR',
                ['BETWEEN', $priceMinSql, $priceMin, $priceMax],
                ['BETWEEN', $priceMaxSql, $priceMin, $priceMax],
            ]);
        } elseif ($priceMin) {
            $query->andWhere(['>', $priceMaxSql, $priceMin]);
        } elseif ($priceMax) {
            $query->andWhere(['<', $priceMinSql, $priceMax]);
        }

        if ($sort === 'desc') {
            $query->orderBy(['price_min' => SORT_DESC]);
        } elseif ($sort === 'asc') {
            $query->orderBy(['price_min' => SORT_ASC]);
        } elseif ($sort === 'popular') {
            $query
                ->addSelect(['COUNT(order.id) as orders_count'])
                ->joinWith([
                    'orders' => fn($q) => $q->select(['id', 'product_id']),
                ])
                ->groupBy('product.id')
                ->orderBy(['orders_count' => SORT_DESC]);
        }

        return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
            'collection' => ProductOutputService::getCollection(
                $query->column(),
            ),
        ]);
    }

    public function actionPopular()
    {
        $offset = Yii::$app->request->get('offset', 0);
        $apiCodes = ResponseCodes::getStatic();
        $collectionQuery = Product::find()
            ->select(['product.id', 'COUNT(order.id) as order_count'])
            ->joinWith(['orders' => fn($q) => $q->select(['id', 'product_id'])])
            ->groupBy('product.id')
            ->orderBy(['order_count' => SORT_DESC, 'product.id' => SORT_ASC])
            ->offset($offset)
            ->limit(4)
            ->asArray();

        return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
            'collection' => ProductOutputService::getCollection(
                $collectionQuery->column(),
            ),
        ]);
    }

    public function actionRandom()
    {
        $apiCodes = ResponseCodes::getStatic();
        $user = User::getIdentity();
        $selectedCategories = array_column($user->categories, 'id');
        $excludeIds = Yii::$app->request->get('exclude_id', []);
        $collectionQuery = Product::find()
            ->select(['product.id'])
            ->joinWith([
                'subcategory' => fn($q) => $q->where([
                    'category_id' => $selectedCategories,
                ]),
            ])
            ->orderBy('RAND()')
            ->limit(20)
            ->asArray();

        if ($excludeIds) {
            $collectionQuery->andWhere(['NOT IN', 'product.id', $excludeIds]);
        }

        return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
            'collection' => ProductOutputService::getCollection(
                $collectionQuery->column(),
            ),
        ]);
    }
}
