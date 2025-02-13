<?php

return [
    'class' => 'yii\swiftmailer\Mailer',
    'transport' => [
        'class' => 'Swift_SmtpTransport',
        'host' => $_ENV['SMTP_HOST'],
        'username' => $_ENV['SMTP_USERNAME'],
        'password' => $_ENV['SMTP_PASSWORD'],
        'port' => $_ENV['SMTP_PORT'],
        'encryption' => $_ENV['SMTP_ENCRYPTION'],
        'streamOptions' => ['ssl' => ['verify_peer' => false]],
    ],
];