<?php

namespace app\services;

use Yii;

class EmailService
{
    public static function sendEmail($to, $subject, $message)
    {
        $mailer = Yii::$app->mailer;
        $from = Yii::$app->params['adminEmail'];

        return $mailer
            ->compose()
            ->setFrom($from)
            ->setTo($to)
            ->setSubject($subject)
            ->setTextBody($message)
            ->send();
    }
}
