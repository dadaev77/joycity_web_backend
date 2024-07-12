<?php

namespace app\controllers\api\v1;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\controllers\api\V1Controller;
use app\models\Feedback;
use app\models\FeedbackAttachment;
use app\models\Order;
use app\models\TypePackaging;
use app\models\User;
use app\services\EmailService;
use Exception;
use Yii;
use yii\web\UploadedFile;

class ProfileScreenController extends V1Controller
{
    public function behaviors()
    {
        $behaviours = parent::behaviors();

        $behaviours['verbFilter']['actions']['self'] = ['get'];
        $behaviours['verbFilter']['actions']['get-user-settings'] = ['get'];
        $behaviours['verbFilter']['actions']['get-my-products'] = ['get'];
        $behaviours['verbFilter']['actions']['update-profile'] = ['put'];
        $behaviours['verbFilter']['actions']['update-user-settings'] = ['put'];
        $behaviours['verbFilter']['actions']['creating-feedback'] = ['put'];
        $behaviours['verbFilter']['actions']['upload-avatar'] = ['post'];
        $behaviours['verbFilter']['actions']['verify-reset-code-email'] = [
            'post',
        ];
        $behaviours['verbFilter']['actions']['code-create-from-email'] = [
            'post',
        ];
        $behaviours['verbFilter']['actions']['get-current-user'] = ['get'];
        $behaviours['verbFilter']['actions']['application-history'] = ['get'];
        $behaviours['verbFilter']['actions']['delete-profile'] = ['delete'];
        $behaviours['verbFilter']['actions']['set-packaging'] = ['post'];
        $behaviours['authenticator']['except'] = [];

        return $behaviours;
    }

    public function actionUploadAvatar()
    {
        $userId = Yii::$app->user->id;

        $user = User::findOne($userId);
        if (!$user) {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->NOT_FOUND,
                ['message' => 'User not found'],
                404,
            );
        }

        $avatar = UploadedFile::getInstanceByName('avatar');

        if (!$avatar) {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->BAD_REQUEST,
                ['message' => 'Avatar not uploaded'],
                400,
            );
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $fileExtension = strtolower($avatar->getExtension());
        if (!in_array($fileExtension, $allowedExtensions)) {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->BAD_REQUEST,
                ['message' => 'Invalid file type'],
                400,
            );
        }

        $fileName = md5(uniqid(rand(), true)) . '.' . $fileExtension;

        $filePath = Yii::getAlias('@app/api/attachment/') . $fileName;
        $relativeFilePath = '/api/attachment/' . $fileName;

        if ($avatar->saveAs($filePath)) {
            if ($user->profile_picture) {
                $oldAvatarPath =
                    Yii::getAlias('@app/api/attachment/') .
                    basename($user->profile_picture);
                if (file_exists($oldAvatarPath)) {
                    unlink($oldAvatarPath);
                }
            }

            $user->profile_picture = $relativeFilePath;
            if (!$user->save(false)) {
                return ApiResponse::byResponseCode(
                    ResponseCodes::getSelf()->INTERNAL_ERROR,
                    ['message' => 'Failed to save user'],
                    500,
                );
            }

            return ApiResponse::byResponseCode(null, [
                'avatar_url' => $relativeFilePath,
            ]);
        } else {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->INTERNAL_ERROR,
                ['message' => 'Failed to upload avatar'],
                500,
            );
        }
    }

    public function actionGetCurrentUser()
    {
        $currentUser = Yii::$app->user->identity;

        if (!$currentUser) {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->NOT_AUTHENTICATED,
                ['message' => 'Пользователь не аутентифицирован'],
                401,
            );
        }

        $result = [
            'name' => $currentUser->name,
            'surname' => $currentUser->surname,
            'organization_name' => $currentUser->organization_name,
            'phone_number' => $currentUser->phone_number,
            'profile_picture' => $currentUser->profile_picture,
            'confirm_email' => $currentUser->confirm_email,
            'email' => $currentUser->email,
        ];
        return ApiResponse::byResponseCode(
            ResponseCodes::getSelf()->SUCCESS,
            $result,
            200,
        );
    }

    public function actionCodeCreateFromEmail()
    {
        $currentUser = Yii::$app->user->identity;
        $email = Yii::$app->request->post('email');

        $userModel = new User();
        $userModel->email = $email;

        if (!$userModel->validate(['email'])) {
            $errors = $userModel->getErrors('email');
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->NOT_VALIDATED,
                ['message' => $errors],
                400,
            );
        }

        if ($currentUser->email === $email && $currentUser->confirm_email) {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->SUCCESS,
                ['message' => 'Ваш адрес электронной почты уже подтвержден.'],
            );
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $code = mt_rand(1000, 9999);

            Yii::$app->cache->set(
                'password_reset_code_' . $currentUser->id,
                $code,
                300,
            );

            $to = $currentUser->email;
            $subject = 'Подтверждение почты';
            $message = 'Код для подтверждения почты: ' . $code;

            $result = EmailService::sendEmail($to, $subject, $message);

            if ($result) {
                $transaction->commit();
                return ApiResponse::byResponseCode(
                    ResponseCodes::getSelf()->SUCCESS,
                    ['message' => 'Код отправлен на указанный email.'],
                );
            } else {
                $transaction->rollBack();
                return ApiResponse::byResponseCode(
                    ResponseCodes::getSelf()->INTERNAL_ERROR,
                    ['message' => 'Не удалось отправить код.'],
                    500,
                );
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->INTERNAL_ERROR,
                ['message' => 'Произошла ошибка при отправке кода.'],
                500,
            );
        }
    }

    public function actionVerifyResetCodeEmail()
    {
        $user = Yii::$app->user->identity;

        $email = Yii::$app->request->post('email');
        $code = Yii::$app->request->post('code');

        if (!$user) {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->NOT_FOUND,
                ['message' => 'User not found.'],
                404,
            );
        }

        $storedCode = Yii::$app->cache->get('password_reset_code_' . $user->id);
        if ($code != $storedCode) {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->NO_ACCESS,
                ['message' => 'Invalid  code.'],
                400,
            );
        }

        if (!Yii::$app->cache->get('password_reset_code_' . $user->id)) {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->NOT_FOUND,
                ['message' => 'Reset code has expired.'],
                404,
            );
        }
        Yii::$app->cache->delete('password_reset_code_' . $user->id);

        $model = User::findOne($user);

        $model->confirm_email = 1;
        $model->email = $email;
        $model->save(false);

        return ApiResponse::byResponseCode(ResponseCodes::getSelf()->SUCCESS, [
            'message' => 'Email confirmed!',
        ]);
    }

    public function actionUpdateProfile()
    {
        $userId = Yii::$app->user->identity;
        $name = Yii::$app->request->post('name');
        $surname = Yii::$app->request->post('surname');
        $organizationName = Yii::$app->request->post('organization_name');
        $phoneNumber = Yii::$app->request->post('phone_number');

        if (!$userId) {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->NOT_FOUND,
                ['error' => 'Отсутствует идентификатор пользователя'],
                404,
            );
        }

        $user = User::findOne($userId);

        if (!$user) {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->NOT_FOUND,
                null,
                404,
            );
        }

        if ($name) {
            $user->name = $name;
        }
        if ($surname) {
            $user->surname = $surname;
        }
        if ($organizationName) {
            $user->organization_name = $organizationName;
        }
        if ($phoneNumber) {
            $user->phone_number = $phoneNumber;
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            if (
                !$user->save(true, [
                    'phone_number',
                    'organization_name',
                    'surname',
                    'name',
                ])
            ) {
                $errors = $user->getErrors();
                return ApiResponse::byResponseCode(
                    ResponseCodes::getSelf()->NOT_VALIDATED,
                    ['message' => $errors],
                    400,
                );
            }

            $transaction->commit();

            return ApiResponse::byResponseCode(null, [
                'success' => 'Пользователь успешно обновлен',
            ]);
        } catch (Exception $e) {
            $transaction->rollBack();
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->NOT_VALIDATED,
                ['message' => $e->getMessage()],
                400,
            );
        }
    }

    public function actionDeleteProfile()
    {
        $user = Yii::$app->user->identity;

        if (!$user) {
            return ApiResponse::byResponseCode(
                User::apiCodes()->NOT_FOUND,
                null,
                404,
            );
        }

        $user->access_token =
            Yii::$app->security->generateRandomString() .
            Yii::$app->security->generateRandomString();
        $user->is_deleted = 1;

        if ($user->save(true, ['is_deleted'])) {
            return ApiResponse::byResponseCode(User::apiCodes()->SUCCESS, [
                'message' => 'Профиль успешно удален.',
            ]);
        }

        return ApiResponse::byResponseCode(
            ResponseCodes::getSelf()->INTERNAL_ERROR,
            ['message' => 'Не удалось удалить профиль.'],
            500,
        );
    }

    public function actionGetUserSettings()
    {
        $userId = Yii::$app->user->identity->id;
        $user = User::findOne($userId);

        $userCategories = $user
            ->getCategories()
            ->asArray()
            ->all();

        $settings = User::getIdentity()->userSettings;

        $response = [
            'user_id' => $userId,
            'categories' => $userCategories,
            'settings' => $settings,
        ];

        return ApiResponse::byResponseCode(
            User::apiCodes()->SUCCESS,
            $response,
        );
    }
    //todo заполнить в postman

    public function actionUpdateUserSettings()
    {
        $userId = Yii::$app->user->identity->id;
        $settings = User::getIdentity()->userSettings;

        $requestData = Yii::$app->getRequest()->getBodyParams();

        $settings->load($requestData, '');

        if ($settings->save()) {
            return ApiResponse::byResponseCode(
                User::apiCodes()->SUCCESS,
                null,
                200,
            );
        } else {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->NOT_VALIDATED,
                ['message' => $settings->errors],
                400,
            );
        }
    }

    public function actionCreatingFeedback()
    {
        $userId = Yii::$app->user->identity->id;
        $electionReferences = Yii::$app->request->post('election_references');
        $inputField = Yii::$app->request->post('input_field');

        if (!$electionReferences || !$inputField) {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->BAD_REQUEST,
                ['message' => 'Не все обязательные параметры переданы'],
                400,
            );
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // todo #newarch feedback
            $model = new Feedback();
            $model->user_id = $userId;
            $model->election_references = $electionReferences;
            $model->input_field = $inputField;

            if (!$model->save()) {
                $errors = $model->getErrors();
                $transaction->rollBack();
                return ApiResponse::byResponseCode(
                    ResponseCodes::getSelf()->NOT_VALIDATED,
                    ['message' => $errors],
                    400,
                );
            }

            $files = UploadedFile::getInstancesByName('file');
            $maxFileSize = 50 * 1024 * 1024;
            $allowedExtensions = [
                'jpg',
                'jpeg',
                'png',
                'gif',
                'mp4',
                'avi',
                'mov',
            ];

            $imageCount = 0;
            $videoCount = 0;

            foreach ($files as $file) {
                $extension = $file->getExtension();
                $fileSize = $file->size;

                if (
                    in_array($extension, $allowedExtensions) &&
                    $fileSize <= $maxFileSize
                ) {
                    // todo #newarch attachment
                    $attachmentModel = new FeedbackAttachment();
                    $attachmentModel->type = $extension;

                    $fileName = md5(uniqid(rand(), true)) . '.' . $extension;
                    $filePath =
                        Yii::getAlias('@app/api/attachment/') . $fileName;
                    $relativeFilePath = '/api/attachment/' . $fileName;

                    if ($file->saveAs($filePath)) {
                        $attachmentModel->file = $relativeFilePath;
                        if (!$attachmentModel->save()) {
                            $errors = $attachmentModel->getErrors();
                            $transaction->rollBack();
                            return ApiResponse::byResponseCode(
                                ResponseCodes::getSelf()->BAD_REQUEST,
                                ['message' => $errors],
                                400,
                            );
                        }

                        if (in_array($extension, ['mp4', 'avi', 'mov'])) {
                            $videoCount++;
                        } else {
                            $imageCount++;
                        }

                        if ($videoCount > 1 || $imageCount > 5) {
                            $transaction->rollBack();
                            return ApiResponse::byResponseCode(
                                ResponseCodes::getSelf()->BAD_REQUEST,
                                [
                                    'message' =>
                                        'Превышен лимит загрузки видео и изображений',
                                ],
                                400,
                            );
                        }

                        $model->link('feedbackAttachments', $attachmentModel);
                    }
                }
            }

            $transaction->commit();
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->SUCCESS,
            );
        } catch (Exception $e) {
            $transaction->rollBack();
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->BAD_REQUEST,
                null,
                400,
            );
        }
    }

    public function actionGetMyCategory()
    {
        $userId = Yii::$app->user->identity->id;
        if (!$userId) {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->NOT_FOUND,
                ['message' => 'Пользователь не найден.'],
                404,
            );
        }
        $myCategory = User::find()
            ->where(['user.id' => $userId])
            ->joinWith('categories')
            ->asArray()
            ->all();
        return ApiResponse::byResponseCode(
            ResponseCodes::getSelf()->SUCCESS,
            $myCategory,
        );
    }

    //получение истории заявок-сделок
    public function actionApplicationHistory()
    {
        $userId = Yii::$app->user->id;
        $query = Order::find()
            ->where(['status' => 8, 'customer_id' => $userId])
            ->asArray()
            ->all();
        return ApiResponse::byResponseCode(
            ResponseCodes::getSelf()->SUCCESS,
            $query,
        );
    }

    //Байер
    public function actionGetMyProducts()
    {
        $userId = Yii::$app->user->identity->id;
        if (!$userId) {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->NOT_FOUND,
                ['message' => 'Пользователь не найден.'],
                404,
            );
        }
        $myProducts = User::find()
            ->select(['id', 'profile_picture'])
            ->with([
                'products' => fn($q) => $q->with('productAttachments'),
            ])
            ->where(['id' => $userId])
            ->asArray()
            ->all();
        return ApiResponse::byResponseCode(
            ResponseCodes::getSelf()->SUCCESS,
            $myProducts,
        );
    }

    public function actionSetPackaging()
    {
        $id = Yii::$app->user->id;
        $request = Yii::$app->request;

        $packagingIds = $request->post('packaging_ids');

        if (empty($packagingIds)) {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->NOT_FOUND,
                ['message' => 'Категории упаковки не выбраны'],
                404,
            );
        }

        $user = User::findOne($id);
        $user->unlinkAll('packagings', true);
        $addedCategories = [];

        foreach ($packagingIds as $packagingId) {
            $packaging = TypePackaging::findOne($packagingId);
            if (empty($packaging)) {
                return ApiResponse::byResponseCode(
                    ResponseCodes::getSelf()->NOT_FOUND,
                    ['message' => 'Одной из катигоий упаковки не сущестует'],
                    404,
                );
            }
            $user->link('packagings', $packaging);
            $addedCategories[] = $packaging;
        }

        $addedCategoriesData = [];
        foreach ($addedCategories as $packaging) {
            $addedCategoriesData[] = [
                'id' => $packaging->id,
                'en_name' => $packaging->en_name,
                'ru_name' => $packaging->ru_name,
                'zh_name' => $packaging->zh_name,
            ];
        }

        return ApiResponse::byResponseCode(null, [
            'message' => 'Категории упаковки установлены успешно',
            'added_categories' => $addedCategoriesData,
        ]);
    }
    public function actionSetDelivery()
    {
        $id = Yii::$app->user->id;
        $request = Yii::$app->request;

        $deliveryIds = $request->post('delivery_ids');

        if (empty($deliveryIds)) {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->NOT_FOUND,
                ['message' => 'Категории доставки не выбраны'],
                404,
            );
        }

        $user = User::findOne($id);
        $user->unlinkAll('delivery', true);
        $addedCategories = [];

        foreach ($deliveryIds as $deliveryId) {
            $delivery = TypePackaging::findOne($deliveryId);
            if (empty($delivery)) {
                return ApiResponse::byResponseCode(
                    ResponseCodes::getSelf()->NOT_FOUND,
                    ['message' => 'Одной из катигоии доставки не сущестует'],
                    404,
                );
            }
            $user->link('delivery', $delivery);
            $addedCategories[] = $delivery;
        }

        $addedCategoriesData = [];
        foreach ($addedCategories as $delivery) {
            $addedCategoriesData[] = [
                'id' => $delivery->id,
                'en_name' => $delivery->en_name,
                'ru_name' => $delivery->ru_name,
                'zh_name' => $delivery->zh_name,
            ];
        }

        return ApiResponse::byResponseCode(null, [
            'message' => 'Категории доставок установлены успешно',
            'added_categories' => $addedCategoriesData,
        ]);
    }
    public function actionChangePassword()
    {
        $userId = Yii::$app->user->id;
        $user = User::findOne($userId);

        $oldPassword = Yii::$app->request->post('old_password');
        $newPassword = Yii::$app->request->post('new_password');
        $confirmPassword = Yii::$app->request->post('confirm_password');

        $currentPasswordHash = $user->password;

        if (
            Yii::$app->security->validatePassword(
                $oldPassword,
                $currentPasswordHash,
            )
        ) {
            if ($newPassword !== $confirmPassword) {
                return ApiResponse::byResponseCode(
                    ResponseCodes::getSelf()->BAD_REQUEST,
                    ['message' => 'Новые пароли не совпадают'],
                    400,
                );
            }
            $user->password = $newPassword;
            if (!$user->validate()) {
                $errors = $user->getErrors();
                return ApiResponse::byResponseCode(
                    ResponseCodes::getSelf()->NOT_VALIDATED,
                    ['message' => $errors['password']],
                    400,
                );
            }

            $user->password = Yii::$app->security->generatePasswordHash(
                $newPassword,
            );

            if (!$user->save(false)) {
                $errors = $user->getErrors();
                return ApiResponse::byResponseCode(
                    ResponseCodes::getSelf()->BAD_REQUEST,
                    ['message' => $errors],
                    400,
                );
            }

            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->SUCCESS,
                ['message' => 'Пароль успешно изменен'],
            );
        } else {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->BAD_REQUEST,
                ['message' => 'Неверный старый пароль'],
                400,
            );
        }
    }
}
