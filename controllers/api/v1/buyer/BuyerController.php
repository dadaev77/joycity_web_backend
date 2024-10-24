<?php

namespace app\controllers\api\v1\buyer;

use app\components\ApiResponse;
use app\controllers\api\v1\BuyerController as BuyerControllerParent;
use app\models\User;
use app\services\output\BuyerOutputService;

class BuyerController extends BuyerControllerParent
{
    public function __construct()
    {
        return 'buyer controller';
        die;
    }
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
            'role' => User::ROLE_BUYER ?? User::ROLE_BUYER_DEMO,
        ]);
        if (!$isset) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        return ApiResponse::info(BuyerOutputService::getEntity($id));
    }
}
