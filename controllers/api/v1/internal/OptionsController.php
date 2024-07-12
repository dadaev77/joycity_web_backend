<?php

namespace app\controllers\api\v1\internal;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\helpers\POSTHelper;
use app\models\AppOption;
use app\services\OptionsService;
use app\services\output\OptionOutputService;

class OptionsController extends InternalController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];
        $behaviors['verbFilter']['actions']['view'] = ['get'];
        $behaviors['verbFilter']['actions']['update'] = ['put'];

        return $behaviors;
    }

    public function actionUpdate(int $id)
    {
        $apiCodes = AppOption::apiCodes();
        $options = OptionsService::updateById(
            $id,
            ...POSTHelper::getPostWithKeys(['value']),
        );

        if ($options->isNotFound) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }
        if ($options->isNotValid) {
            return ApiResponse::codeErrors(
                $apiCodes->NOT_VALID,
                $options->reason,
            );
        }

        return ApiResponse::info(
            OptionOutputService::getEntity($options->result->id),
        );
    }

    public function actionView(int $id)
    {
        $apiCodes = AppOption::apiCodes();
        $isset = AppOption::isset(['id' => $id]);

        if (!$isset) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        return ApiResponse::info(OptionOutputService::getEntity($id));
    }

    public function actionIndex()
    {
        $options = AppOption::find();

        return ApiResponse::collection(
            OptionOutputService::getCollection($options->column()),
        );
    }
}
