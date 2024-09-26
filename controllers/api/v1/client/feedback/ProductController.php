<?php

namespace app\controllers\api\v1\client\feedback;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\controllers\api\v1\ClientController;
use app\models\Base;
use app\models\FeedbackProduct;
use app\models\Product;
use app\models\User;
use app\services\AttachmentService;
use app\services\FeedbackService;
use app\services\output\FeedbackProductOutputService;
use app\services\RatingService;
use Exception as BaseException;
use Yii;
use yii\web\UploadedFile;

class ProductController extends ClientController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['create'] = ['post'];
        $behaviors['verbFilter']['actions']['collection'] = ['get'];
        $behaviors['verbFilter']['actions']['can-create-feedback'] = ['get'];
        array_unshift($behaviors['access']['rules'], [
            'actions' => ['create'],
            'allow' => false,
            'matchCallback' => fn() => User::getIdentity()->role === User::ROLE_CLIENT_DEMO || !User::getIdentity()->is_verified,
        ]);
        $behaviors['access']['denyCallback'] = static function () {
            $response =
                User::getIdentity()->role === User::ROLE_CLIENT_DEMO ?
                ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_AUTHENTICATED) :
                ApiResponse::byResponseCode(ResponseCodes::getStatic()->NO_ACCESS_FOR_NOT_VERIFIED);
            Yii::$app->response->data = $response;
        };

        return $behaviors;
    }

    public function actionCreate()
    {
        $apiCodes = FeedbackProduct::apiCodes();
        $request = Yii::$app->request;
        $user = User::getIdentity();
        $productId = $request->post('product_id');
        $transaction = null;

        if (!Product::isset(['id' => $productId])) {
            return ApiResponse::code($apiCodes->BAD_REQUEST);
        }

        if (!FeedbackService::canCreateFeedbackProduct($productId, $user->id)) {
            return ApiResponse::code($apiCodes->NO_ACCESS);
        }

        try {
            $newReview = new FeedbackProduct();
            $newReview->load($request->post(), '');
            $newReview->product_id = $productId;
            $newReview->created_by = $user->id;
            $newReview->created_at = date('Y-m-d H:i:s');

            if (!$newReview->validate()) {
                return ApiResponse::codeErrors(
                    $apiCodes->NOT_VALID,
                    $newReview->getFirstErrors(),
                );
            }

            $transaction = Yii::$app->db->beginTransaction();

            if (!$newReview->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $newReview->getFirstErrors(),
                );
            }

            $images = UploadedFile::getInstancesByName('images');

            if ($images) {
                $attachmentSaveResponse = AttachmentService::writeFilesCollection(
                    $images,
                    5,
                    1,
                );

                if (!$attachmentSaveResponse->success) {
                    $transaction?->rollBack();

                    return ApiResponse::code($apiCodes->INTERNAL_ERROR, [
                        'errors' => ['images' => 'Failed to save images'],
                    ]);
                }

                $newReview->linkAll(
                    'attachments',
                    $attachmentSaveResponse->result,
                    [
                        'type' => Base::TYPE_DEFAULT,
                    ],
                );
            }

            $ratingStatus = RatingService::updateProductRating(
                $newReview->product_id,
            );

            if (!$ratingStatus->success) {
                $transaction?->rollBack();

                return ApiResponse::code(
                    $apiCodes->ERROR_SAVE,
                    $ratingStatus->reason,
                );
            }

            $transaction?->commit();

            return ApiResponse::code($apiCodes->SUCCESS, [
                'info' => FeedbackProductOutputService::getEntity(
                    $newReview->id,
                    'small',
                ),
            ]);
        } catch (BaseException) {
            $transaction?->rollBack();

            return ApiResponse::code($apiCodes->INTERNAL_ERROR);
        }
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
                'small',
            ),
        ]);
    }
    public function actionCanCreateFeedback(int $id)
    {
        $userId = Yii::$app->user->id;
        $apiCodes = FeedbackProduct::apiCodes();

        if (FeedbackService::canCreateFeedbackProduct($id, $userId)) {
            return ApiResponse::code($apiCodes->SUCCESS);
        }

        return ApiResponse::code($apiCodes->NO_ACCESS);
    }
}
