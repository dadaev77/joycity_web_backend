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
        $baseDir = Yii::getAlias('@app/controllers');
        $baseNamespace = 'app\\controllers';
        $template = 'controller';
        $this->createFile($name, $baseDir, $baseNamespace, $template);
    }

    /**
     * Создание модели шаблонной структуры
     * @param string $name
     * @return string
     */
    public function actionModel($name)
    {
        $baseDir = Yii::getAlias('@app/models');
        $baseNamespace = 'app\\models';
        $template = 'model';
        $this->createFile($name, $baseDir, $baseNamespace, $template);
    }

    /**
     * Создание сервиса шаблонной структуры
     * @param string $name
     * @return string
     */
    public function actionService($name)
    {
        $baseDir = Yii::getAlias('@app/services');
        $baseNamespace = 'app\\services';
        $template = 'service';
        $this->createFile($name, $baseDir, $baseNamespace, $template);
    }

    /**
     * Создание задачи шаблонной структуры
     * @param string $name
     * @return string
     */
    public function actionJob($name)
    {
        $baseDir = Yii::getAlias('@app/jobs');
        $baseNamespace = 'app\\jobs';
        $template = 'job';
        $this->createFile($name, $baseDir, $baseNamespace, $template);
    }

    private function createFile(string $name, string $baseDir, string $baseNamespace, string $template)
    {
        $parts = explode('/', $name);
        $className = array_pop($parts);
        $className = $className . ucfirst($template);

        $namespace = $baseNamespace;
        if (!empty($parts)) {
            $namespace .= '\\' . implode('\\', $parts);
        }

        $subDir = !empty($parts) ? implode(DIRECTORY_SEPARATOR, $parts) : '';
        $directory = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $subDir;

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filePath = $directory . DIRECTORY_SEPARATOR . $className . '.php';
        if (file_exists($filePath)) {
            echo "\033[31mФайл $filePath уже существует.\033[0m\n";
            return;
        }

        $template = file_get_contents(__DIR__ . '/../templates/' . $template . '.php');
        $template = str_replace('$namespace', $namespace, $template);
        $template = str_replace('$className', $className, $template);

        file_put_contents($filePath, $template);
        echo "\033[32mФайл $className успешно создан.\033[0m\n";
    }
}
