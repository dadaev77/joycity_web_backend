<?php

namespace app\services;

use Yii;
use Throwable;
use \app\components\ApiResponse;
use \app\components\response\ResponseCodes;

class TwoFactorAuthService
{
    /**
     *  Это сервис брат
     *  че тут и как делать, спросишь?
     * 
     *  я без понятия, я только сделал шаблон
     */

    public function __construct()
    {
        /**
         *  тут делаешь че то
         */
    }

    public function sendCode($user_id, $telegram_id)
    {
        $code = $this->generateCode();

        $user = \app\models\User::findOne(['id' => $user_id]);

        if (!$user) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_FOUND, [
            'message' => 'User not found'
        ]);
        if ($user->telegram == null) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->NO_ACCESS, [
            'message' => 'User has no telegram nickname'
        ]);

        $otp = new \app\models\OTP2FAModel();
        $otp->user_id = $user_id;
        $otp->telegram_id = $telegram_id;
        $otp->otp_code = $code;
        $otp->save();

        return $code;
    }


    public function verifyCode($user_id, $code)
    {
        $otp = \app\models\OTP2FAModel::findOne(['user_id' => $user_id, 'otp_code' => $code]);

        if (!$otp) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST);

        if (strtotime($otp->expires_at) > time()) {
            $otp->is_used = true;
            $otp->save();
            return ApiResponse::byResponseCode(ResponseCodes::getStatic()->SUCCESS, [
                'message' => 'Code verified successfully used'
            ]);
        }

        return ApiResponse::byResponseCode(ResponseCodes::getStatic()->NO_ACCESS, [
            'message' => 'Code expired'
        ]);
    }

    private function generateCode()
    {
        return rand(100000, 999999);
    }

    /**
     * тут короче используешь сервис и делаешь че то
     * лучше всего использовать через статические методы (грязи меньше)
     */

    public function __destruct()
    {
        /**
         *  тут делаешь че то
         */
    }
}
