<?php

namespace app\controllers\api\v1\manager;

use app\controllers\api\v1\ManagerController;
use app\models\User;
use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use Yii;

class ClientController extends ManagerController
{
    //
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['update-markup'] = ['post'];
        return $behaviors;
    }

    public function actionUpdateMarkup()
    {
        $user = User::getIdentity();
        // if (!$user->can('update-markup')) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->NO_ACCESS);

        $request = Yii::$app->request;
        $markup = $request->post('markup');
        $clientId = $request->post('client_id');

        if (empty($clientId) || empty($markup)) return ApiResponse::byResponseCode(
            ResponseCodes::getStatic()->BAD_REQUEST,
            [
                'client_id' => 'Поле обязательное для заполнения',
                'markup' => 'Поле обязательное для заполнения',
            ]
        );

        $client = User::findOne($clientId);
        if (!$client) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_FOUND, [
            'message' => 'Не найден пользователь с таким ID',
        ]);

        $client->markup = $markup;
        $client->save();

        return ApiResponse::byResponseCode(ResponseCodes::getStatic()->SUCCESS, [
            'message' => 'Наценка успешно обновлена',
            'markup' => "Текущая наценка пользователя $client->id: $client->markup%",
        ]);
    }
}
