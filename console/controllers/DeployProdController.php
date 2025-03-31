<?php

namespace app\console\controllers;

use app\services\CustomFieldsService;
use app\services\OptionsService;
use yii\console\Controller;
use yii\console\ExitCode;

class DeployProdController extends Controller
{
    public function actionAll()
    {
        echo 'Deploying app options' . PHP_EOL;
        $this->actionAppOptions();

        echo PHP_EOL;

        echo 'Deploying static fields' . PHP_EOL;
        $this->actionStaticFields();

        return ExitCode::OK;
    }

    public function actionAppOptions()
    {
        $insertedIds = OptionsService::deployFields();

        if (!empty($insertedIds->result)) {
            echo 'Inserted option key: ' .
                implode(', ', $insertedIds->result) .
                "\n";
        } else {
            echo "No new options inserted.\n";
        }

        return ExitCode::OK;
    }

    public function actionStaticFields()
    {
        $result = CustomFieldsService::deployStaticFields();

        echo "Deployed static fields, inserted/updated: {$result->result['inserted']}, failed: {$result->result['failed']}" .
            PHP_EOL;

        return ExitCode::OK;
    }
}
