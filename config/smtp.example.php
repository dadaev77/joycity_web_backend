<?php

return [
    'class' => 'yii\swiftmailer\Mailer',
    'transport' => [
        'class' => 'Swift_SmtpTransport',
        'host' => 'YOUR_SMTP_HOST',
        'username' => 'YOUR_SMTP_USERNAME',
        'password' => 'YOUR_SMTP_PASSWORD',
        'port' => 'YOUR_SMTP_PORT',
        'encryption' => 'YOUR_SMTP_ENCRYPTION',
        'streamOptions' => ['ssl' => ['verify_peer' => false]],
    ],
];