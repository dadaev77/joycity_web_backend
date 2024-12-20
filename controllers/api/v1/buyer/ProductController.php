<?php

namespace app\controllers\api\v1\buyer;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\controllers\api\v1\BuyerController;
use app\models\Attachment;
use app\models\Product;
use app\models\ProductLinkAttachment;
use app\models\User;
use app\services\AttachmentService;
use app\services\output\ProductOutputService;
use app\services\RateService;
use app\services\SaveModelService;
use Throwable;
use Yii;
use yii\web\UploadedFile;
use linslin\yii2\curl\Curl;
use app\services\TranslationService;
use app\services\UserActionLogService as Log;

class ProductController extends BuyerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['view'] = ['get'];
        $behaviors['verbFilter']['actions']['create'] = ['post'];
        $behaviors['verbFilter']['actions']['update'] = ['put'];
        $behaviors['verbFilter']['actions']['delete'] = ['delete'];
        $behaviors['verbFilter']['actions']['my'] = ['get'];
        array_unshift($behaviors['access']['rules'], [
            'actions' => ['create', 'update', 'delete'],
            'allow' => false,
            'matchCallback' => fn() => User::getIdentity()->role === User::ROLE_BUYER_DEMO,
        ]);
        $behaviors['access']['denyCallback'] = static function () {
            $response =
                User::getIdentity()->role === User::ROLE_BUYER_DEMO ?
                ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_AUTHENTICATED) :
                ApiResponse::code(ResponseCodes::getStatic()->NO_ACCESS);
            Yii::$app->response->data = $response;
        };

        return $behaviors;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/buyer/product/create",
     *     summary="Создать новый продукт",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "price"},
     *             @OA\Property(property="name", type="string", example="Продукт 1"),
     *             @OA\Property(property="price", type="number", format="float", example=99.99),
     *             @OA\Property(property="description", type="string", example="Описание продукта"),
     *             @OA\Property(property="subcategory_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Продукт успешно создан."
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Неверный запрос."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера."
     *     )
     * )
     */
    public function actionCreate()
    {
        try {
            $apiCodes = Product::apiCodes();
            $user = User::getIdentity();
            $request = Yii::$app->request;

            Log::info('request', json_encode($request->post()));

            $images = UploadedFile::getInstancesByName('images');

            if (!$images) {
                return ApiResponse::codeErrors($apiCodes->NOT_VALID, [
                    'images' => 'Param `images` is empty',
                ]);
            }

            $transaction = Yii::$app->db->beginTransaction();

            $product = new Product();

            $product->load(
                array_diff_key(
                    $request->post(),
                    array_flip([
                        'rating',
                        'feedback_count',
                        'buyer_id',
                        'is_deleted',
                    ]),
                ),
                '',
            );

            // translate product attributes
            $translation = TranslationService::translateProductAttributes($request->post('name'), $request->post('description'));
            $translations = $translation->result;

            foreach ($translations as $key => $value) {
                $product->{"name_$key"} = $value['name'];
                $product->{"description_$key"} = $value['description'];
            }
            // set buyer id
            $product->buyer_id = $user->id;
            // set currency from user settings
            $product->currency = $user->settings->currency;
            // assign prices as is, without conversion
            $product->range_1_price = $request->post('range_1_price') ?? 0;
            $product->range_2_price = $request->post('range_2_price') ?? 0;
            $product->range_3_price = $request->post('range_3_price') ?? 0;
            $product->range_4_price = $request->post('range_4_price') ?? 0;

            $productSave = SaveModelService::validateAndSave(
                $product,
                [],
                $transaction,
            );

            Log::info('productSave', json_encode($productSave));

            if (!$productSave->success) {
                return $productSave->apiResponse;
            }

            $attachmentSaveResponse = AttachmentService::writeFilesCollection(
                $images,
            );

            if (!$attachmentSaveResponse->success) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->INTERNAL_ERROR,
                    ['images' => 'Failed to save images'],
                );
            }




            $product->linkAll('attachments', $attachmentSaveResponse->result, [
                'type' => ProductLinkAttachment::TYPE_DEFAULT,
            ]);

            $transaction?->commit();

            return ApiResponse::info(
                ProductOutputService::getEntity(
                    $product->id,
                    'small'
                ),
            );
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/buyer/product/update/{id}",
     *     summary="Обновить продукт",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID продукта.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "price"},
     *             @OA\Property(property="name", type="string", example="Обновленный продукт"),
     *             @OA\Property(property="price", type="number", format="float", example=89.99),
     *             @OA\Property(property="description", type="string", example="Обновленное описание продукта"),
     *             @OA\Property(property="subcategory_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Продукт успешно обновлен."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Продукт не найден."
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Нет доступа к продукту."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера."
     *     )
     * )
     */
    public function actionUpdate(int $id)
    {
        $apiCodes = Product::apiCodes();
        $user = User::getIdentity();
        $request = Yii::$app->request;
        $product = Product::findOne(['id' => $id]);
        $repeatImagesToKeep = $request->post('repeat_images_to_keep');

        $transaction = null;

        if (!$product) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        if ($product->buyer_id !== $user->id) {
            return ApiResponse::code($apiCodes->NO_ACCESS);
        }

        try {
            $transaction = Yii::$app->db->beginTransaction();

            $product->load(
                array_diff_key(
                    $request->post(),
                    array_flip([
                        'rating',
                        'feedback_count',
                        'buyer_id',
                        'is_deleted',
                        'range_1_price',
                        'range_2_price',
                        'range_3_price',
                        'range_4_price',
                    ]),
                ),
                '',
            );
            // set currency from user settings
            $product->currency = $user->settings->currency;
            // assign prices as is, without conversion
            $product->range_1_price = $request->post('range_1_price') ?? 0;
            $product->range_2_price = $request->post('range_2_price') ?? 0;
            $product->range_3_price = $request->post('range_3_price') ?? 0;
            $product->range_4_price = $request->post('range_4_price') ?? 0;

            $productSave = SaveModelService::validateAndSave(
                $product,
                [],
                $transaction,
            );

            if (!$productSave->success) {
                return $productSave->apiResponse;
            }

            $attachmentsToLink = [];
            $images = UploadedFile::getInstancesByName('images');

            if ($images) {
                $attachmentSaveResponse = AttachmentService::writeFilesCollection(
                    $images,
                );

                if (!$attachmentSaveResponse->success) {
                    $transaction?->rollBack();

                    return ApiResponse::byResponseCode(
                        $apiCodes->INTERNAL_ERROR,
                        ['errors' => ['images' => 'Failed to save images']],
                    );
                }

                $attachmentsToLink = array_merge(
                    $attachmentsToLink,
                    $attachmentSaveResponse->result,
                );
            }

            if ($repeatImagesToKeep) {
                $repeatProduct = Product::findOne(['id' => $id]);

                if ($repeatProduct && $repeatProduct->buyer_id === $user->id) {
                    $attachmentsToKeep = Attachment::find()
                        ->joinWith([
                            'productLinkAttachments' => fn($q) => $q->where([
                                'product_id' => $repeatProduct->id,
                            ]),
                        ])
                        ->where(['attachment.id' => $repeatImagesToKeep])
                        ->all();

                    $attachmentsToLink = array_merge(
                        $attachmentsToLink,
                        $attachmentsToKeep,
                    );
                }
            }

            if ($attachmentsToLink) {
                $product->linkAll('attachments', $attachmentsToLink, [
                    'type' => productLinkAttachment::TYPE_DEFAULT,
                ]);
            }

            $transaction?->commit();

            return ApiResponse::info(ProductOutputService::getEntity(
                $id,
                'small'
            ));
        } catch (Throwable $e) {
            $transaction?->rollBack();

            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/buyer/product/view/{id}",
     *     summary="Получить информацию о продукте",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID продукта.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Информация о продукте успешно получена."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Продукт не найден."
     *     )
     * )
     */
    public function actionView(int $id)
    {
        $apiCodes = Product::apiCodes();
        $isset = Product::isset(['id' => $id]);

        if (!$isset) {
            return ApiResponse::byResponseCode($apiCodes->NOT_FOUND);
        }

        return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
            'info' => ProductOutputService::getEntity(
                $id,
                'small'
            ),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/buyer/product/delete/{id}",
     *     summary="Удалить продукт",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID продукта.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Продукт успешно удален."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Продукт не найден."
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Нет доступа к продукту."
     *     )
     * )
     */
    public function actionDelete(int $id)
    {
        $apiCodes = Product::apiCodes();
        $user = User::getIdentity();
        $product = Product::findOne(['id' => $id]);

        if (!$product) {
            return ApiResponse::byResponseCode($apiCodes->NOT_FOUND);
        }

        if ($product->buyer_id !== $user->id) {
            return ApiResponse::byResponseCode($apiCodes->NO_ACCESS);
        }

        $product->is_deleted = 1;

        if (!$product->save()) {
            return ApiResponse::byResponseCode($apiCodes->ERROR_SAVE, [
                'errors' => $product->getFirstErrors(),
            ]);
        }

        return ApiResponse::byResponseCode($apiCodes->SUCCESS);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/buyer/product/my",
     *     summary="Получить мои продукты",
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         required=false,
     *         description="Смещение для пагинации.",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список продуктов успешно получен."
     *     )
     * )
     */
    public function actionMy(int $offset = 0)
    {
        $apiCodes = Product::apiCodes();
        $user = User::getIdentity();
        $idsCollection = Product::find()
            ->select('id')
            ->where(['buyer_id' => $user->id])
            ->offset($offset)
            ->limit(20)
            ->column();

        return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
            'collection' => ProductOutputService::getCollection(
                $idsCollection,
                'small'
            ),
        ]);
    }
}
