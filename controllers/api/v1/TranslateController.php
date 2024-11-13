<?php

namespace app\controllers\api\v1;

use app\components\ApiResponse;
use app\controllers\api\V1Controller;
use app\models\ChatTranslate;
use app\services\output\ChatTranslateOutputService;
use app\services\TranslationService;
use Throwable;
use Yii;

class TranslateController extends V1Controller
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['chat-message'] = ['post'];

        return $behaviors;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/chat-message",
     *     summary="Обработка сообщения чата",
     *     description="Этот метод принимает оригинальный текст сообщения и возвращает переведенные тексты на русский, английский и китайский языки. Если перевод уже существует, он будет возвращен. В противном случае, создается новая запись.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"original_text", "message_key", "chat_id"},
     *             @OA\Property(property="original_text", type="string", example="Привет, как дела?"),
     *             @OA\Property(property="message_key", type="string", example="unique_message_key"),
     *             @OA\Property(property="chat_id", type="integer", example=123)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ с переведенными текстами"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка при сохранении перевода"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера"
     *     )
     * )
     */
    public function actionChatMessage()
    {
        $request = Yii::$app->request;

        $apiCodes = ChatTranslate::apiCodes();

        try {
            $originalText = $request->post('original_text');
            $messageKey = $request->post('message_key');
            $chatId = $request->post('chat_id');
            $translationModel = ChatTranslate::findOne([
                'message_key' => $messageKey,
            ]);

            if ($translationModel) {
                if (
                    $translationModel->ru !== '.' &&
                    $translationModel->en !== '.' &&
                    $translationModel->zh !== '.'
                ) {
                    return ApiResponse::info(
                        ChatTranslateOutputService::getEntity(
                            $translationModel->id,
                        ),
                    );
                }
            } else {
                $translationModel = new ChatTranslate();
                $translationModel->message_key = $messageKey;
                $translationModel->ru = '.';
                $translationModel->en = '.';
                $translationModel->zh = '.';

                if (!$translationModel->save()) {
                    return ApiResponse::codeErrors(
                        $apiCodes->ERROR_SAVE,
                        $translationModel->getFirstErrors(),
                    );
                }
            }

            $translationText = TranslationService::translate($originalText);
            if (!$translationText->success) {
                return ApiResponse::code(
                    $apiCodes->INTERNAL_ERROR,
                    $translationText->reason,
                );
            }
            $translationModel->ru = $translationText->result['ru'];
            $translationModel->en = $translationText->result['en'];
            $translationModel->zh = $translationText->result['zh'];

            if (!$translationModel->save()) {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $translationModel->getFirstErrors(),
                );
            }

            return ApiResponse::info(
                ChatTranslateOutputService::getEntity($translationModel->id),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }
}
