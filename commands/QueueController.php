<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\queue\cli\Listener;
use yii\queue\cli\RunCommand;

/**
 * Команды для работы с очередями.
 */
class QueueController extends Controller
{
    /**
     * Запуск прослушивания очереди.
     */
    public function actionListen()
    {
        // Запуск обработчика очереди в режиме ожидания новых задач
        $listener = Yii::createObject(Listener::class);
        $listener->run();
    }

    /**
     * Разовая обработка задач в очереди.
     */
    public function actionRun()
    {
        // Обработка задач в очереди один раз
        $command = Yii::createObject(RunCommand::class);
        $command->run([]);
    }
}