<?php

namespace app\console\controllers;

use yii\console\Controller;
use Yii;

class MakeController extends Controller
{
    /**
     * Создание контроллера шаблонной структуры
     * @param string $name
     * @return string
     */
    public function actionController($name)
    {
        echo 'controller ' . $name;
    }

    /**
     * Создание модели шаблонной структуры
     * @param string $name
     * @return string
     */
    public function actionModel($name)
    {
        echo 'model ' . $name;
    }

    /**
     * Создание сервиса шаблонной структуры
     * @param string $name
     * @return string
     */
    public function actionService($name)
    {

        echo 'service ' . $name;
    }

    /**
     * Создание задачи шаблонной структуры
     * @param string $name
     * @return string
     */
    public function actionJob($name)
    {
        echo 'job ' . $name;
    }
}
