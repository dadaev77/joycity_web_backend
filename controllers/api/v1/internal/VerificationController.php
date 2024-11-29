<?php

namespace app\controllers\api\v1\internal;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\models\User;
use app\models\UserVerificationRequest;
use app\services\output\UserVerificationRequestOutputService;
use Throwable;
use Yii;
use yii\base\Exception;

/**
 * @OA\Get(
 *     path="/api/v1/internal/verification",
 *     summary="Получить список запросов на верификацию",
 *     security={{"Bearer":{}}},
 *     tags={"Verification"},
 *     @OA\Parameter(
 *         name="role",
 *         in="query",
 *         required=false,
 *         description="Роль пользователя, создавшего запрос",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="from",
 *         in="query",
 *         required=false,
 *         description="Дата начала создания запроса",
 *         @OA\Schema(type="string", format="date")
 *     ),
 *     @OA\Parameter(
 *         name="to",
 *         in="query",
 *         required=false,
 *         description="Дата окончания создания запроса",
 *         @OA\Schema(type="string", format="date")
 *     ),
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         required=false,
 *         description="Статус запроса",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Список запросов на верификацию успешно получен"
 *     )
 * )
 */
class VerificationController extends InternalController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['approve'] = ['put'];
        $behaviors['verbFilter']['actions']['index'] = ['get'];
        $behaviors['verbFilter']['actions']['view'] = ['get'];

        return $behaviors;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/internal/verification",
     *     summary="Получить список запросов на верификацию",
     *     security={{"Bearer":{}}},
     *     tags={"Verification"},
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         required=false,
     *         description="Роль пользователя, создавшего запрос",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         required=false,
     *         description="Дата начала создания запроса",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         required=false,
     *         description="Дата окончания создания запроса",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Статус запроса",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список запросов на верификацию успешно получен"
     *     )
     * )
     */
    public function actionIndex()
    {
        try {
            $request = Yii::$app->request;
            $query = UserVerificationRequest::find();
            $role = $request->get('role');

            if ($role) {
                $query->joinWith([
                    'createdBy' => fn($q) => $q->select(['role'])->where([
                        'role' => $role,
                    ]),
                ]);
            }

            if ($from = $request->get('from')) {
                $query->andWhere([
                    '>=',
                    'user_verification_request.created_at',
                    $from,
                ]);
            }

            if ($to = $request->get('to')) {
                $query->andWhere([
                    '<=',
                    'user_verification_request.created_at',
                    date('Y-m-d', strtotime($to . ' +1 day')),
                ]);
            }

            $status = $request->get('status');

            if ($status) {
                $query->andWhere(['status' => $status]);
            }

            return ApiResponse::collection(
                UserVerificationRequestOutputService::getCollection(
                    $query->column(),
                ),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/internal/verification/approve/{id}",
     *     summary="Одобрить запрос на верификацию",
     *     security={{"Bearer":{}}},
     *     tags={"Verification"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID запроса на верификацию",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Запрос на верификацию успешно одобрен"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Запрос на верификацию не найден"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации параметров"
     *     )
     * )
     */
    public function actionApprove(int $id)
    {
        $apiCodes = UserVerificationRequest::apiCodes();

        try {
            $user = User::getIdentity();
            $request = UserVerificationRequest::findOne(['id' => $id]);

            if (!$request) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if ($request->status === UserVerificationRequest::STATUS_APPROVED) {
                return ApiResponse::code($apiCodes->ALREADY_APPROVED);
            }

            $request->status = UserVerificationRequest::STATUS_APPROVED;
            $request->approved_by_id = $user->id;

            $transaction = Yii::$app->db->beginTransaction();

            if (!$request->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $request->getFirstErrors(),
                );
            }

            $verifiedUser = $request->createdBy;
            $verifiedUser->is_verified = 1;

            if (!$verifiedUser->save(true, ['is_verified'])) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $verifiedUser->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                UserVerificationRequestOutputService::getEntity($request->id),
            );
        } catch (Exception | Throwable) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::code($apiCodes->INTERNAL_ERROR);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/internal/verification/view/{id}",
     *     summary="Просмотр запроса на верификацию",
     *     security={{"Bearer":{}}},
     *     tags={"Verification"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID запроса на верификацию",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Запрос на верификацию успешно найден"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Запрос на верификацию не найден"
     *     )
     * )
     */
    public function actionView(int $id)
    {
        $apiCodes = User::apiCodes();
        $user = User::findOne(['id' => $id], true);

        if (!$user) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        return ApiResponse::info(
            UserVerificationRequestOutputService::getEntity($id),
        );
    }
}
