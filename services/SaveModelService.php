<?php

namespace app\services;

use app\components\ApiResponse;
use app\helpers\POSTHelper;
use app\models\Base;
use Yii;
use yii\db\Transaction;

class SaveModelService
{
    public Base $model;
    public array $apiResponse = [];
    public bool $success = false;

    public static function validateAndSave(
        Base $model,
        array $params = [],
        Transaction $transaction = null,
    ): SaveModelService {
        $saveStatus = new self();
        $saveStatus->model = $model;

        if (!$model->validate($params ?: null)) {
            $saveStatus->apiResponse = ApiResponse::codeErrors(
                $model::apiCodes()->BAD_REQUEST,
                $model->getFirstErrors(),
            );

            $transaction?->rollBack();

            return $saveStatus;
        }

        if (!$model->save(false, $params ?: null)) {
            $saveStatus->apiResponse = ApiResponse::codeErrors(
                $model::apiCodes()->ERROR_SAVE,
                $model->getFirstErrors(),
            );

            $transaction?->rollBack();

            return $saveStatus;
        }

        $saveStatus->success = true;

        return $saveStatus;
    }

    public static function loadValidateAndSave(
        Base $model,
        array $params = [],
        Transaction $transaction = null,
        bool $validateAllKeys = false,
    ): SaveModelService {
        if ($params) {
            $model->load(POSTHelper::getPostWithKeys($params), '');
        } else {
            $model->load(Yii::$app->request->post(), '');
        }

        return self::validateAndSave(
            $model,
            $validateAllKeys ? [] : $params,
            $transaction,
        );
    }
}
