<?php

namespace app\controllers\api\v1\client;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\controllers\api\v1\ClientController;
use app\models\Chat;
use app\models\User;
use app\models\UserVerificationRequest;
use app\services\chat\ChatConstructorService;
use app\services\notification\NotificationConstructor;
use app\services\output\UserVerificationRequestOutputService;
use app\services\RateService;
use Throwable;
use Yii;

class VerificationController extends ClientController
{
    public function behaviors()
    {
        $behaviours = parent::behaviors();

        $behaviours['verbFilter']['actions']['create'] = ['post'];
        array_unshift($behaviours['access']['rules'], [
            'actions' => ['create'],
            'allow' => false,
            'matchCallback' => fn() => User::getIdentity()->role === User::ROLE_CLIENT_DEMO,
        ]);

        $behaviours['access']['denyCallback'] = static function () {
            $response =
                User::getIdentity()->role === User::ROLE_CLIENT_DEMO ?
                ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_AUTHENTICATED) :
                false;
            Yii::$app->response->data = $response;
        };

        return $behaviours;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/client/verification/create",
     *     summary="Создать запрос на верификацию",
     *     description="Создает новый запрос на верификацию для текущего пользователя.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number", format="float", example=100.00),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Запрос на верификацию успешно создан"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Некорректные данные"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Пользователь уже верифицирован или есть активный запрос"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера"
     *     )
     * )
     */
    public function actionCreate()
    {
        $apiCodes = UserVerificationRequest::apiCodes();

        try {
            $user = User::getIdentity();

            if ($user->is_verified) {
                return ApiResponse::code($apiCodes->ALREADY_VERIFIED);
            }

            $activeVerificationRequest = UserVerificationRequest::findOne([
                'created_by_id' => $user->id,
                'status' => UserVerificationRequest::STATUS_WAITING,
            ]);

            if ($activeVerificationRequest) {
                return ApiResponse::code($apiCodes->HAS_ACTIVE_REQUEST);
            }

            $randomManager = User::find()
                ->select(['id'])
                ->where(['role' => User::ROLE_MANAGER])
                ->orderBy('RAND()')
                ->one();

            $newRequest = new UserVerificationRequest([
                'created_by_id' => $user->id,
                'manager_id' => $randomManager->id,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => UserVerificationRequest::STATUS_WAITING,
                'amount' => Yii::$app->params['verificationAmount'],
            ]);

            $transaction = Yii::$app->db->beginTransaction();

            if (!$newRequest->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $newRequest->getFirstErrors(),
                );
            }

            $conversation = ChatConstructorService::createChatVerification(
                Chat::GROUP_CLIENT_MANAGER,
                [$user->id, $randomManager->id],
                $newRequest->id,
            );

            if (!$conversation->success) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $conversation->reason,
                );
            }

            $transaction?->commit();

            NotificationConstructor::verificationVerificationCreated(
                $newRequest->manager_id,
                $newRequest->id,
            );

            return ApiResponse::codeInfo(
                $apiCodes->SUCCESS,
                UserVerificationRequestOutputService::getEntity(
                    $newRequest->id,
                ),
            );
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }
}
