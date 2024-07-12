<?php

namespace app\controllers\api\v1;

use app\components\ApiResponse;
use app\controllers\api\V1Controller;
use app\helpers\POSTHelper;
use app\models\FeedbackUser;
use app\models\search\User;
use app\services\AttachmentService;
use app\services\output\FeedbackUserOutputService;
use Exception as BaseException;
use Yii;
use yii\web\UploadedFile;

class FeedbackController extends V1Controller
{
    public function actionCreate()
    {
        $user = Yii::$app->user->identity;
        $apiCodes = User::apiCodes();
        $images = UploadedFile::getInstancesByName('images');
        $postParams = POSTHelper::getPostWithKeys(['reason', 'text']);
        $transaction = null;
        try {
            $feedback = new FeedbackUser();
            $feedback->load($postParams, '');
            $feedback->created_by = $user->id;
            $feedback->created_at = date('Y-m-d H:i:s');

            if (!$feedback->validate()) {
                return ApiResponse::byResponseCode($apiCodes->NOT_VALID, [
                    'errors' => $feedback->getFirstErrors(),
                ]);
            }

            $transaction = Yii::$app->db->beginTransaction();
            if (!$feedback->save()) {
                $transaction?->rollBack();

                return ApiResponse::byResponseCode($apiCodes->ERROR_SAVE, [
                    'errors' => $feedback->getFirstErrors(),
                ]);
            }
            if ($images) {
                $attachmentSaveResponse = AttachmentService::writeFilesCollection(
                    $images,
                    5,
                    1,
                );

                if (!$attachmentSaveResponse->success) {
                    $transaction?->rollBack();

                    return ApiResponse::byResponseCode(
                        $apiCodes->INTERNAL_ERROR,
                        [
                            'errors' => [
                                'images' => 'Не удалось сохранить картинку',
                            ],
                        ],
                    );
                }

                $feedback->linkAll(
                    'attachments',
                    $attachmentSaveResponse->result,
                );
            }

            $transaction?->commit();

            return ApiResponse::byResponseCode(null, [
                'info' => FeedbackUserOutputService::getEntity($feedback->id),
            ]);
        } catch (BaseException $e) {
            $transaction?->rollBack();
            return ApiResponse::byResponseCode($apiCodes->INTERNAL_ERROR);
        }
    }
}
