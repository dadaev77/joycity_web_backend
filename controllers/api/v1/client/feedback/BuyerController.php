<?php

namespace app\controllers\api\v1\client\feedback;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\controllers\api\v1\ClientController;
use app\models\Base;
use app\models\FeedbackBuyer;
use app\models\User;
use app\services\AttachmentService;
use app\services\FeedbackService;
use app\services\output\FeedbackBuyerOutputService;
use app\services\RatingService;
use Throwable;
use Yii;
use yii\web\UploadedFile;

class BuyerController extends ClientController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['create'] = ['post'];
        $behaviors['verbFilter']['actions']['collection'] = ['get'];
        $behaviors['verbFilter']['actions']['сan-create-feedback'] = ['get'];
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
        $apiCodes = FeedbackBuyer::apiCodes();
        $transaction = null;
        $request = Yii::$app->request;
        $user = User::getIdentity();
        $buyerId = $request->post('buyer_id');

        if (!FeedbackService::canCreateFeedbackBuyer($buyerId, $user->id)) {
            return ApiResponse::code($apiCodes->NO_ACCESS);
        }

        try {
            if (!User::isset(['id' => $buyerId])) {
                return ApiResponse::code($apiCodes->BAD_REQUEST);
            }

            $newReview = new FeedbackBuyer();
            $newReview->load($request->post(), '');
            $newReview->buyer_id = $buyerId;
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
                );

                if (!$attachmentSaveResponse->success) {
                    $transaction?->rollBack();

                    return ApiResponse::codeErrors($apiCodes->INTERNAL_ERROR, [
                        'images' => 'Failed to save images',
                    ]);
                }

                $newReview->linkAll(
                    'attachments',
                    $attachmentSaveResponse->result,
                    [
                        // todo link type
                        'type' => Base::TYPE_DEFAULT,
                    ],
                );
            }

            $ratingStatus = RatingService::updateBuyerRating(
                $newReview->buyer_id,
            );

            if (!$ratingStatus->success) {
                $transaction?->rollBack();

                return ApiResponse::code(
                    $apiCodes->ERROR_SAVE,
                    $ratingStatus->reason,
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                FeedbackBuyerOutputService::getEntity($newReview->id),
            );
        } catch (Throwable $e) {
            $transaction?->rollBack();

            return ApiResponse::internalError($e);
        }
    }

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

    public function actionCanCreateFeedback(int $id)
    {
        $userId = Yii::$app->user->id;
        $apiCodes = FeedbackBuyer::apiCodes();

        if (FeedbackService::canCreateFeedbackBuyer($id, $userId)) {
            return ApiResponse::code($apiCodes->SUCCESS);
        }

        return ApiResponse::code($apiCodes->NO_ACCESS);
    }
}
