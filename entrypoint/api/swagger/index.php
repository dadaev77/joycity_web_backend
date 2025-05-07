<?php

$loginPage = './login.php';
$contentPage = './content.php';
$getAuth = $_COOKIE['auth'] ?? false;

$authData = [
    'login' => 'joycity',
    'password' => 'Ap9s!i9wP8'
];

$postData = $_POST ?? [];

$isAuth = auth($postData, $authData);


if ($isAuth) {
    setcookie('auth', true, time() + 3600, '/');
    header('Location: content.php');
    exit;
}

if ($isAuth) {
    include $contentPage;
} else {
    include $loginPage;
}

function auth($postData, $authData)
{
    if (!empty($postData)) {
        return $postData['login'] === $authData['login'] && $postData['password'] === $authData['password'];
    }
    return false;
}
