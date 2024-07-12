<?php

namespace app\controllers\api\v1\manager;

use app\components\ApiResponse;
use app\controllers\api\v1\ManagerController;
use app\models\Chat;
use app\models\User;
use app\services\output\ChatOutputService;
use Throwable;
use Yii;

class ChatController extends ManagerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];

        return $behaviors;
    }

    public function actionIndex()
    {
        try {
            $user = User::getIdentity();
            $request = Yii::$app->request;
            $type = $request->get('group', '');
            $isArchive = (int) $request->get('is_archive', 0);
            $query = Chat::find()
                ->select(['chat.id'])
                ->joinWith([
                    'chatUsers' => fn($q) => $q
                        ->select(['id', 'user_id', 'chat_id'])
                        ->where([
                            'user_id' => $user->id,
                        ]),
                ])
                ->where(['group' => $type]);

            if ($isArchive === 1) {
                $query->andWhere(['is_archive' => 1]);
            }

            if ($isArchive === 0) {
                $query->andWhere(['is_archive' => 0]);
            }

            return ApiResponse::collection(
                ChatOutputService::getCollection($query->column()),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }
}
