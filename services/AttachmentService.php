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

class AttachmentService
{
    public const PUBLIC_PATH = 'attachments';
    public const AllowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'heic', 'webp'];
    public const AllowedVideoExtensions = ['mp4', 'avi', 'mov'];
    public const IMAGE_SIZES = [
        'small' => 256,
        'medium' => 512,
        'large' => 1024,
        'xlarge' => 2048,
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
            $existingAttachments = $mainModel->$relationName;
            if ($existingAttachments) {
                foreach ($existingAttachments as $existingAttachment) {
                    $fileToDelete =
                        Yii::getAlias('@app/entrypoint/api/attachment/') .
                        basename($existingAttachment->$fileAttribute);
                    if (file_exists($fileToDelete)) {
                        unlink($fileToDelete);
                    }
                }
                $mainModel->unlinkAll($relationName, true);
            }

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
            $not_file = false;
            if (is_string($file)) {
                $not_file = true;
                $tmpfile = tmpfile();
                $metaData = stream_get_meta_data($tmpfile);
                $tmpfileName = $metaData['uri'];
                $getfile = file_get_contents($file);
                file_put_contents($tmpfileName, $getfile);
                $mimeType = mime_content_type($tmpfileName);
                $extension = match ($mimeType) {
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                    default => 'jpg',
                };

                $file = new UploadedFile([
                    'tempName' => $tmpfileName . '.' . $extension,
                    'name' => basename($file),
                    'size' => filesize($tmpfileName),
                    'type' => $mimeType,
                ]);
            }

            $extension = $not_file ? array_reverse(explode('.', $file->tempName))[0] : $file->getExtension();

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
            // Проверяем, является ли $file объектом UploadedFile
            if (!$file instanceof UploadedFile) {
                // Если это не объект UploadedFile, создаем его из временного файла
                $tmpfile = tmpfile();
                $metaData = stream_get_meta_data($tmpfile);
                $tmpfileName = $metaData['uri'];
                $getfile = file_get_contents($file);
                file_put_contents($tmpfileName, $getfile);
                $mimeType = mime_content_type($tmpfileName);
                $extension = match ($mimeType) {
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                    default => 'jpg',
                };

                $file = new UploadedFile([
                    'tempName' => $tmpfileName,
                    'name' => basename($file),
                    'size' => filesize($tmpfileName),
                    'type' => $mimeType,
                ]);
            }

            foreach (self::IMAGE_SIZES as $label => $size) {

                $fileModelResponse = self::writeFileWithModel($file, $size, $label);
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

    public static function writeFileWithModel(UploadedFile $file, int $size = 1024, string $name = 'large'): ResultAnswer
    {
        try {
            $extension = pathinfo($file->name, PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = match ($file->type) {
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                    default => 'jpg',
                };
            }
            $mimeType = $file->type;
            $pathName = Yii::$app->security->generateRandomString(16) . '_' . $name;
            $path = '/' . self::PUBLIC_PATH . "/$pathName.$extension";
            $fullPath = self::getFilesPath() . "/$pathName.$extension";

            if (in_array($extension, self::AllowedImageExtensions, true)) {
                $image = new Imagick($file->tempName);
                $image->autoOrient();
                $originalWidth = $image->getImageWidth();
                $originalHeight = $image->getImageHeight();
                $scale = min($size / $originalWidth, $size / $originalHeight);
                $newWidth = (int)($originalWidth * $scale);
                $newHeight = (int)($originalHeight * $scale);
                $canvas = new Imagick();
                $canvas->newImage($size, $size, new \ImagickPixel('white'));
                $canvas->setImageFormat('webp');
                $image->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
                $canvas->compositeImage($image, Imagick::COMPOSITE_OVER, (int)(($size - $newWidth) / 2), (int)(($size - $newHeight) / 2));
                $canvas->writeImage($fullPath);
                $image->destroy();
                $canvas->destroy();
                $fileSize = filesize($fullPath);
            } else {
                $status = rename($file->tempName, $fullPath);
                if (!$status) {
                    return Result::error(['errors' => ['Error save file']]);
                }
                $fileSize = filesize($fullPath);
            }
            chmod($fullPath, 0666);

            if (!file_exists($fullPath)) {
                return Result::error(['errors' => ['File does not exist after saving']]);
            }

            $attachment = new Attachment([
                'path' => $path,
                'size' => $fileSize,
                'extension' => $extension,
                'mime_type' => $mimeType,
                'img_size' => $name,
            ]);

            if (!$attachment->validate()) {
                return Result::notValid([
                    'errors' => $attachment->getFirstErrors(),
                ]);
            }

            if (!$attachment->save()) {
                return Result::notValid(['errors' => $attachment->getFirstErrors()]);
            }

            return Result::success($attachment);
        } catch (Exception $e) {
            Yii::$app->telegramLog->send('error', 'Ошибка при обработке изображения: ' . $e->getMessage());
            return Result::error(['errors' => $e->getMessage()]);
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
