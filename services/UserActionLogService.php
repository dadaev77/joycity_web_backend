<?php

namespace app\services;

use app\controllers\RawController;

class UserActionLogService
{
    public static function info(string $message): void
    {
        file_put_contents(RawController::ACTION_LOG_FILE, self::renderMessage($message, 'primary'), FILE_APPEND);
    }
    public static function error(string $message): void
    {
        file_put_contents(RawController::ACTION_LOG_FILE, self::renderMessage($message, 'danger'), FILE_APPEND);
    }
    public static function danger(string $message): void
    {
        file_put_contents(RawController::ACTION_LOG_FILE, self::renderMessage($message, 'danger'), FILE_APPEND);
    }
    public static function warning(string $message): void
    {
        file_put_contents(RawController::ACTION_LOG_FILE, self::renderMessage($message, 'warning'), FILE_APPEND);
    }
    public static function debug(string $message): void
    {
        file_put_contents(RawController::ACTION_LOG_FILE, self::renderMessage($message, 'secondary'), FILE_APPEND);
    }
    public static function success(string $message): void
    {
        file_put_contents(RawController::ACTION_LOG_FILE, self::renderMessage($message, 'success'), FILE_APPEND);
    }
    public static function log(string $message): void
    {
        file_put_contents(RawController::ACTION_LOG_FILE, self::renderMessage($message), FILE_APPEND);
    }
    private static function renderMessage(string $message, string $type = 'dark'): string
    {
        $message = str_replace("\n", "<br>", $message);
        $message = str_replace("    ", "&nbsp;&nbsp;&nbsp;&nbsp;", $message);
        return "<p class='text-$type'>[-][-] " . date('Y-m-d H:i') . " [-][-]$message</p> \n";
    }
}
