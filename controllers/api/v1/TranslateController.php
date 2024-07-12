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
