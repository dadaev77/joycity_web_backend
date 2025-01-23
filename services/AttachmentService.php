<?php

namespace app\services;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\components\responseFunction\Result;
use app\components\responseFunction\ResultAnswer;
use app\models\Attachment;
use Exception;
use finfo;
use Imagick;
use Yii;
use yii\web\HttpException;
use yii\web\UploadedFile;
// intervention image
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class AttachmentService
{
    public const PUBLIC_PATH = 'attachments';
    public const AllowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'heic'];
    public const AllowedVideoExtensions = ['mp4', 'avi', 'mov'];
    public const IMAGE_SIZES = [
        ['width' => 256, 'height' => 256, 'name' => 'small'],
        ['width' => 512, 'height' => 512, 'name' => 'medium'],
        ['width' => 1024, 'height' => 1024, 'name' => 'large'],
    ];

    /**
     * @throws HttpException
     */
    public static function saveAttachments(
        array $files,
        $attachmentModelName,
        $fileAttribute,
        $mainModel,
        $relationName,
    ) {
        $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $allowedVideoExtensions = ['mp4', 'avi', 'mov'];

        $imageCount = 0;
        $videoCount = 0;

        $maxFileSize = 50 * 1024 * 1024;
        $attachmentResults = [];

        foreach ($files as $file) {
            $extension = $file->getExtension();
            $fileSize = $file->size;

            if (
                in_array($extension, $allowedImageExtensions) &&
                $imageCount < 9
            ) {
                $imageCount++;
            } elseif (
                in_array($extension, $allowedVideoExtensions) &&
                $videoCount < 3
            ) {
                $videoCount++;
            } else {
                return ApiResponse::byResponseCode(
                    ResponseCodes::getSelf()->BAD_REQUEST,
                    [
                        'message' =>
                        'Ошибка: недопустимый тип файла или превышено количество',
                    ],
                    400,
                );
            }

            if ($fileSize > $maxFileSize) {
                return ApiResponse::byResponseCode(
                    ResponseCodes::getSelf()->BAD_REQUEST,
                    [
                        'message' =>
                        'Ошибка: превышен максимальный размер файла (не более 50 МБ)',
                    ],
                    400,
                );
            }
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Удалем существующие вложения возможны доработки
            $existingAttachments = $mainModel->$relationName;
            if ($existingAttachments) {
                foreach ($existingAttachments as $existingAttachment) {
                    // Удаляем соответствующий файл с сервера
                    $fileToDelete =
                        Yii::getAlias('@app/entrypoint/api/attachment/') .
                        basename($existingAttachment->$fileAttribute);
                    if (file_exists($fileToDelete)) {
                        unlink($fileToDelete);
                    }
                }
                $mainModel->unlinkAll($relationName, true);
            }
            // Проходимся по загруженным файлам и сохраняем их в базе данных
            foreach ($files as $file) {
                $extension = $file->getExtension();
                $fileName = md5(uniqid(rand(), true)) . '.' . $extension;
                $filePath =
                    Yii::getAlias('@app/entrypoint/api/attachment/') .
                    $fileName;
                $relativeFilePath = 'entrypoint/api/attachment/' . $fileName;

                if ($file->saveAs($filePath)) {
                    $attachmentModel = new $attachmentModelName();
                    $attachmentModel->type = $extension;
                    $attachmentModel->$fileAttribute = $relativeFilePath;

                    if (!$attachmentModel->save()) {
                        $errors = $attachmentModel->getErrors();

                        return ApiResponse::byResponseCode(
                            ResponseCodes::getSelf()->NOT_VALIDATED,
                            ['message' => $errors],
                            400,
                        );
                    }

                    $mainModel->link($relationName, $attachmentModel);
                    $attachmentResults[] = [
                        'id' => $attachmentModel->id,
                        'type' => $attachmentModel->type,
                        'file' => $attachmentModel->$fileAttribute,
                    ];
                } else {
                    return ApiResponse::byResponseCode(
                        ResponseCodes::getSelf()->BAD_REQUEST,
                        ['message' => 'Ошибка: не удалось сохранить файл'],
                        400,
                    );
                }
            }

            $transaction->commit();
        } catch (Exception $e) {
            // Если произошла ошибка во время транзакции, ткатываем ее и выбрасываем исключение
            Yii::$app->telegramLog->send('error', 'Ошибка при сохранении вложений: ' . $e->getMessage());
            $transaction->rollBack();
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->INTERNAL_ERROR,
                ['message' => $e->getMessage()],
                500,
            );
        }
        return $attachmentResults;
    }

    /**
     * @param UploadedFile[] $files
     * @return ResultAnswer
     */
    public static function writeFilesCollection(
        array $files,
        int $maxImageCount = 9,
        int $maxVideoCount = 3,
    ) {
        $imageCount = 0;
        $videoCount = 0;

        $maxFileSize = 50 * 1024 * 1024;

        foreach ($files as $file) {
            $extension = $file->getExtension();
            $fileSize = $file->size;

            if (in_array($extension, self::AllowedImageExtensions, true)) {
                $imageCount++;
            } elseif (
                in_array($extension, self::AllowedVideoExtensions, true)
            ) {
                $videoCount++;
            } else {
                return Result::notValid([
                    'errors' => [
                        'file_type' => 'Ошибка: недопустимый тип файла',
                    ],
                ]);
            }

            if ($fileSize > $maxFileSize) {
                return Result::notValid([
                    'errors' => [
                        'file_size' =>
                        'Ошибка: превышен максимальный размер файла (не более 50 МБ)',
                    ],
                ]);
            }
        }

        if ($imageCount > $maxImageCount || $videoCount > $maxVideoCount) {
            return Result::notValid([
                'errors' => [
                    'file_count' => 'Ошибка: превышено количество',
                ],
            ]);
        }

        $transaction = Yii::$app->db->beginTransaction();
        $out = [];

        foreach ($files as $file) {
            // create images for all sizes
            foreach (self::IMAGE_SIZES as $size) {
                $fileModelResponse = self::writeFileWithModel($file, $size['width'], $size['height'], $size['name']);


                if (!$fileModelResponse->success) {
                    $transaction?->rollBack();

                    return Result::error([
                        'errors' => [
                            'file_save' => 'Ошибка: ошибка записи файлов',
                        ],
                    ]);
                }

                $out[] = $fileModelResponse->result;
            }
        }

        $transaction?->commit();

        return Result::success($out);
    }

    public static function writeFileWithModel(UploadedFile $file, int $width = 1024, int $height = 1024, string $name = 'large'): ResultAnswer
    {
        try {
            $extension = pathinfo($file->name, PATHINFO_EXTENSION);
            $mimeType = $file->type;
            $pathName = Yii::$app->security->generateRandomString(16);
            $path = '/' . self::PUBLIC_PATH . "/$pathName.$extension";
            $fullPath = self::getFilesPath() . "/$pathName.$extension";
            $size = $file->size;

            if (in_array($extension, self::AllowedImageExtensions, true)) {
                $manager = new ImageManager(new GdDriver());
                $image = $manager->read($file->tempName);

                $image->resize(null, $height, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->resizeCanvas($width, $height, 'center', false, '#ffffff')
                    ->toWebp(80)->save($fullPath);

                $mimeType = mime_content_type($fullPath);
                $size = filesize($fullPath);
            } else {
                $status = rename($file->tempName, $fullPath);
                if (!$status) {
                    return Result::error(['errors' => ['Error save file']]);
                }
            }
            chmod($fullPath, 0666);

            // Debugging: Check if the file exists
            if (!file_exists($fullPath)) {
                return Result::error(['errors' => ['File does not exist after saving']]);
            }

            $attachment = new Attachment([
                'path' => $path,
                'size' => $size,
                'extension' => $extension,
                'mime_type' => $mimeType,
                'img_size' => $name,
            ]);

            if (!$attachment->validate()) {
                return Result::notValid([
                    'errors' => $attachment->getFirstErrors(),
                ]);
            }

            $attachment->save();

            return Result::success($attachment);
        } catch (Exception $e) {
            // Логируем ошибку
            Yii::$app->telegramLog->send('error', 'Ошибка при обработке изображения: ' . $e->getMessage());

            // Возвращаем ошибку
            return Result::error(['errors' => ['image_processing' => 'Ошибка при обработке изображения']]);
        }
    }

    public static function getFilesPath()
    {
        return Yii::getAlias('@webroot') . '/' . self::PUBLIC_PATH;
    }


    public static function writeFileWithModelByPath(string $link): ResultAnswer
    {
        $fileContent = file_get_contents(Yii::getAlias('@webroot') . $link);
        if ($fileContent === false) return Result::error();

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $extension = pathinfo($link, PATHINFO_EXTENSION);
        $pathName = time() . '_' . random_int(1e3, 9e3) . '_' . md5($fileContent);
        $path = '/' . self::PUBLIC_PATH . "/$pathName.$extension";
        $fullPath = self::getFilesPath() . "/$pathName.$extension";
        $status = file_put_contents($fullPath, $fileContent);
        if (!$status) return Result::error(['errors' => ['Error save file']]);
        chmod($fullPath, 0666);
        // create attachment instance
        $attachment = new Attachment([
            'path' => $path,
            'size' => strlen($fileContent),
            'extension' => $extension,
            'mime_type' => $fileInfo->buffer($fileContent),
        ]);
        // validate attachment
        if (!$attachment->validate()) return Result::notValid(['errors' => $attachment->getFirstErrors()]);
        // save attachment
        $attachment->save();
        return Result::success($attachment);
    }
}
