<?php

namespace app\services;

use app\controllers\RawController;
use app\models\User;

class UserActionLogService
{
    private static $controller = 'controller not defined';
    public static function setController(string $controller): void
    {
        self::$controller = $controller;
    }

    private static function prependLog(string $message): void
    {
        $logFile = RawController::ACTION_LOG_FILE;
        $currentContent = file_exists($logFile) ? file_get_contents($logFile) : '';
        $newContent = $message . $currentContent;
        file_put_contents($logFile, $newContent);
    }
    public static function info(mixed $message): void
    {
        self::prependLog(self::renderMessage($message, 'primary'));
    }
    public static function error(mixed $message): void
    {
        self::prependLog(self::renderMessage($message, 'danger'));
    }
    public static function danger(mixed $message): void
    {
        self::prependLog(self::renderMessage($message, 'danger'));
    }
    public static function warning(mixed $message): void
    {
        self::prependLog(self::renderMessage($message, 'warning'));
    }
    public static function debug(mixed $message): void
    {
        self::prependLog(self::renderMessage($message, 'secondary'));
    }
    public static function success(mixed $message): void
    {
        self::prependLog(self::renderMessage($message, 'success'));
    }
    public static function log(mixed $message): void
    {
        self::prependLog(self::renderMessage($message));
    }
    private static function renderMessage(mixed $message, string $type = 'dark'): string
    {
        $user = User::getIdentity();
        $email = $user ? $user->email : 'unauthorized user';
        if (is_array($message)) {
            $message = json_encode($message);
        } elseif (!is_string($message)) {
            $message = (string) $message;
        }

        $message = str_replace("\n", "<br>", $message);
        $message = str_replace("    ", "&nbsp;&nbsp;&nbsp;&nbsp;", $message);

        return "<p class='text-$type'>[-][-] $email [-][-] " . date('Y-m-d H:i') . " [-][-] <span class='text-muted'> " . self::$controller . "</span> [-][-] $message </p> \n";
        // return "<p class='text-$type'>[-][-] $email [-][-] " . date('Y-m-d H:i') . " [-][-] $message </p> \n";
    }
}
