<?php

namespace app\controllers\api\v1\manager;

use app\components\ApiResponse;
use app\controllers\api\v1\ManagerController;
use app\models\User;
use app\models\UserVerificationRequest;
use app\services\output\UserVerificationRequestOutputService;
use Throwable;
use Yii;
use yii\base\Exception;

class VerificationController extends ManagerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];
        $behaviors['verbFilter']['actions']['view'] = ['get'];
        $behaviors['verbFilter']['actions']['accept'] = ['put'];

        return $behaviors;
    }

    public function actionView(int $id)
    {
        $apiCodes = UserVerificationRequest::apiCodes();
        $isset = UserVerificationRequest::isset(['id' => $id]);

        if (!$isset) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        return ApiResponse::codeInfo(
            $apiCodes->SUCCESS,
            UserVerificationRequestOutputService::getEntity($id),
        );
    }

    public function actionIndex()
    {
        $apiCodes = UserVerificationRequest::apiCodes();
        $user = User::getIdentity();
        $query = UserVerificationRequest::find()
            ->select(['id'])
            ->where(['status' => UserVerificationRequest::STATUS_WAITING])
            ->andWhere(['manager_id' => $user->id]);

        return ApiResponse::codeCollection(
            $apiCodes->SUCCESS,
            UserVerificationRequestOutputService::getCollection(
                $query->column(),
            ),
        );
    }

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
}
