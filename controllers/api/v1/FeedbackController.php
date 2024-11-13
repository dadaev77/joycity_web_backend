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
    /**
     * @OA\Post(
     *     path="/api/v1/feedback/create",
     *     summary="Создание отзыва",
     *     description="Этот метод позволяет пользователю создать новый отзыв. Пользователь должен предоставить причину и текст отзыва. Также можно прикрепить изображения.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reason", "text"},
     *             @OA\Property(property="reason", type="string", example="Проблема с продуктом"),
     *             @OA\Property(property="text", type="string", example="Я столкнулся с проблемой при использовании продукта."),
     *             @OA\Property(property="images", type="array", @OA\Items(type="string", format="binary"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Отзыв успешно создан",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="reason", type="string", example="Проблема с продуктом"),
     *                 @OA\Property(property="text", type="string", example="Я столкнулся с проблемой при использовании продукта."),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-01T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации данных",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="reason", type="array", @OA\Items(type="string", example="Причина обязательна")),
     *                 @OA\Property(property="text", type="array", @OA\Items(type="string", example="Текст отзыва обязателен"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Внутренняя ошибка сервера")
     *         )
     *     )
     * )
     */
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
