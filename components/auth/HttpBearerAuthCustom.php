<?php

namespace app\components\auth;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use yii\filters\auth\HttpBearerAuth;

class HttpBearerAuthCustom extends HttpBearerAuth
{
    /**
     * @inheritdoc
     */
    public function handleFailure($response)
    {
        $response->data = ApiResponse::byResponseCode(
            ResponseCodes::getSelf()->NOT_AUTHENTICATED
        );
    }
}
