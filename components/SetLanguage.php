<?php

namespace app\components;

use yii\filters\auth\HttpBearerAuth;
use Yii;


class SetLanguage extends \yii\base\Component
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [ 'class' => HttpBearerAuth::class ];
        return $behaviors;
    }

    public function init()
    {
        $authHeader = Yii::$app->request->getHeaders()->get('Authorization');
        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $identity = Yii::$app->user->identityClass::findIdentityByAccessToken($token);
            if ($identity !== null) {
                Yii::$app->language = $identity->getSettings()->application_language ?? 'en-US';
            }
        }
        parent::init();
    }
}