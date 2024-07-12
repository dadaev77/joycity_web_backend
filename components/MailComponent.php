<?php

use yii\base\Component;

class MailerComponent extends Component
{
    public function sendEmail($recipientEmail, $subject, $message)
    {

        $mailer = Yii::$app->mailer;
        $mailer->compose()
            ->setTo($recipientEmail)
            ->setFrom(['example@example.com' => 'Your Name'])
            ->setSubject($subject)
            ->setHtmlBody($message)
            ->send();
    }
}