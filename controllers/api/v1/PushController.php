<?php

namespace app\controllers\api\v1;

use app\models\PushNotification;
use yii\rest\ActiveController;

class PushController extends ActiveController
{
    public $modelClass = PushNotification::class;

    // Здесь можно добавить дополнительные действия для обработки событий
} 