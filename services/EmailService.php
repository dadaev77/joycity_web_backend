<?php

namespace app\services;

use Yii;

class EmailService
{
    public static function sendEmail($to, $subject, $message)
    {
        $mailer = Yii::$app->mailer;
        $from = Yii::$app->params['adminEmail'];

        try {
            $result = $mailer
                ->compose()
                ->setFrom($from)
                ->setTo($to)
                ->setSubject($subject)
                ->setTextBody($message)
                ->send();

            return $result;
        } catch (Throwable $e) {
            Yii::$app->telegramLog->send('error', 'Ошибка при отправке email: ' . $e->getMessage());
            return false;
        }
    }
}
