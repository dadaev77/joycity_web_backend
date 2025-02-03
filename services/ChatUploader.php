<?php

namespace app\services;

use Imagick;
use app\models\ChatAttachment;
use Yii;

class ChatUploader
{
    protected $uploadPath = '@app/entrypoint/api/uploads/chats/';

    public function __construct()
    {
        $this->uploadPath = Yii::getAlias($this->uploadPath);
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0777, true);
        }
    }

    protected static function generateUniqueFileName($originalName)
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        return $baseName . '_' . uniqid() . '.' . $extension;
    }

    public static function uploadImages(array $images)
    {
        $uploader = new self();

        $attachments = [];
        foreach ($images as $image) {
            $uniqueName = self::generateUniqueFileName($image->name);
            $attachment = [
                'type' => 'image',
                'original_name' => $image->name,
                'file_name' => $uniqueName,
                'file_path' => $image->tempName,
                'file_size' => $image->size,
                'mime_type' => $image->type,
            ];

            $targetPath = $uploader->uploadPath . $attachment['file_name'];
            if (move_uploaded_file($image->tempName, $targetPath)) {
                $attachment['file_path'] = '/uploads/chats/' . $attachment['file_name'];
            } else {
                throw new \Exception("Не удалось переместить файл: " . $image->name);
            }

            $imagick = new Imagick($targetPath);
            $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
            $imagick->setImageCompressionQuality(75);
            $imagick->stripImage();
            $imagick->writeImage($targetPath);
            $attachment['file_path'] = '/uploads/chats/' . $attachment['file_name'];

            $attachments[] = $attachment;
        }
        return $attachments;
    }
    public static function uploadVideos(array $videos)
    {
        return $videos;
    }
    public static function uploadFiles(array $files)
    {
        return $files;
    }
    public static function uploadAudios(array $audio)
    {
        $uploader = new self();

        $attachments = [];
        foreach ($audio as $audioFile) {
            $uniqueName = self::generateUniqueFileName($audioFile->name);
            $attachment = [
                'type' => 'audio',
                'original_name' => $audioFile->name,
                'file_name' => $uniqueName,
                'file_path' => $audioFile->tempName,
                'file_size' => $audioFile->size,
                'mime_type' => $audioFile->type,
            ];

            $targetPath = $uploader->uploadPath . $attachment['file_name'];
            if (move_uploaded_file($audioFile->tempName, $targetPath)) {
                $attachment['file_path'] = '/uploads/chats/' . $attachment['file_name'];
            } else {
                throw new \Exception("Не удалось переместить аудио файл: " . $audioFile->name);
            }

            $attachments[] = $attachment;
        }
        return $attachments;
    }
}
