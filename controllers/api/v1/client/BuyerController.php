<?php

namespace app\controllers\api\v1\client;

use app\components\ApiResponse;
use app\controllers\api\v1\ClientController;
use app\models\User;
use app\services\output\BuyerOutputService;

class BuyerController extends ClientController
{
    public function behaviors()
    {
        $behaviours = parent::behaviors();
        $behaviours['verbFilter']['actions']['view'] = ['get'];

        return $behaviours;
    }

    public function actionView(int $id)
    {
        $apiCodes = User::apiCodes();
        $isset = User::isset([
            'id' => $id,
            'role' => User::ROLE_BUYER,
        ]);
        if (!$isset) {
            return ApiResponse::byResponseCode($apiCodes->NOT_FOUND, null, 404);
        }

        return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
            'info' => BuyerOutputService::getEntity($id),
        ]);
    }
}
