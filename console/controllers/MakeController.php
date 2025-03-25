<?php

namespace app\console\controllers;

use yii\console\Controller;
use Yii;

class MakeController extends Controller
{
    public function actionController($name)
    {
        echo 'controller ' . $name;
    }
    public function actionModel($name)
    {
        echo 'model ' . $name;
    }
    public function actionService($name)
    {
        echo 'service ' . $name;
    }
    public function actionJob($name)
    {
        echo 'job ' . $name;
    }
}
