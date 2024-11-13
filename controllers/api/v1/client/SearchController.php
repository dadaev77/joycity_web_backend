<?php

namespace app\controllers\api\v1\client;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\controllers\api\v1\ClientController;
use app\models\Category;
use app\models\Product;
use app\models\Subcategory;
use app\models\User;
use app\services\output\ProductOutputService;
use app\services\RateService;
use Yii;
use yii\helpers\Inflector;

class SearchController extends ClientController
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

    /**
     * @OA\Get(
     *     path="/api/v1/client/search/hints",
     *     summary="Получить подсказки по запросу",
     *     description="Возвращает подсказки на основе введенного запроса.",
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         description="Поисковый запрос",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ с подсказками",
     *         @OA\JsonContent(
     *             @OA\Property(property="collection", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="type", type="string", example="category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Некорректный запрос"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Подсказки не найдены"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v1/client/search",
     *     summary="Поиск продуктов",
     *     description="Ищет продукты по заданным параметрам.",
     *     @OA\Parameter(
     *         name="subcategory_id",
     *         in="query",
     *         required=true,
     *         description="ID подкатегории",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=false,
     *         description="Поисковый запрос",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="price_min",
     *         in="query",
     *         required=false,
     *         description="Минимальная цена",
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="price_max",
     *         in="query",
     *         required=false,
     *         description="Максимальная цена",
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         required=false,
     *         description="Смещение для пагинации",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         required=false,
     *         description="Сортировка результатов (asc, desc, popular)",
     *         @OA\Schema(type="string", enum={"asc", "desc", "popular"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ с результатами поиска",
     *         @OA\JsonContent(
     *             @OA\Property(property="collection", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Некорректные данные"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Продукты не найдены"
     *     )
     * )
     */
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
                'small', // Size of output images
            ),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/client/search/popular",
     *     summary="Получить популярные продукты",
     *     description="Возвращает список популярных продуктов.",
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         required=false,
     *         description="Смещение для пагинации",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ с популярными продуктами",
     *         @OA\JsonContent(
     *             @OA\Property(property="collection", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Популярные продукты не найдены"
     *     )
     * )
     */
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
                'small', // Size of output images
            ),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/client/search/random",
     *     summary="Получить случайные продукты",
     *     description="Возвращает случайные продукты.",
     *     @OA\Parameter(
     *         name="exclude_id",
     *         in="query",
     *         required=false,
     *         description="ID продуктов, которые нужно исключить",
     *         @OA\Schema(type="array", @OA\Items(type="integer"))
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ с случайными продуктами",
     *         @OA\JsonContent(
     *             @OA\Property(property="collection", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function actionRandom()
    {
        $apiCodes = ResponseCodes::getStatic();
        $user = User::getIdentity();
        $selectedCategories = array_column($user->categories, 'id');
        $excludeIds = Yii::$app->request->get('exclude_id', []);
        $collectionQuery = Product::find()
            ->select(['product.id'])
            ->joinWith([
                'subcategory'
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
                'small', // Size of output images
            ),
        ]);
    }
}
