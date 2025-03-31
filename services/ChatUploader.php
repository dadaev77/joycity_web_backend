<?php

namespace app\services;

use Imagick;
use Yii;

class ChatUploader
{
    protected $uploadPath = '@app/entrypoint/api/uploads/chats/';
    protected static $sizes = [
        'sm' => 256,
        'md' => 512,
        'lg' => 1024,
        'xl' => 2048,
    ];

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
                'sizes' => []
            ];

            $targetPath = $uploader->uploadPath . $attachment['file_name'];
            if (move_uploaded_file($image->tempName, $targetPath)) {
                $attachment['file_path'] = '/uploads/chats/' . $attachment['file_name'];
            } else {
                throw new \Exception("Не удалось переместить файл: " . $image->name);
            }

            foreach (self::$sizes as $label => $size) {
                $imagick = new Imagick($targetPath);
                $imagick->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1, true);
                $resizedFileName = pathinfo($attachment['file_name'], PATHINFO_FILENAME) . "_{$label}." . pathinfo($attachment['file_name'], PATHINFO_EXTENSION);
                $resizedPath = $uploader->uploadPath . $resizedFileName;
                $imagick->writeImage($resizedPath);
                $attachment['sizes'][$size] = '/uploads/chats/' . $resizedFileName;
            }
            $attachment['file_path'] =  $attachment['sizes']['256'];
            $attachments[] = $attachment;
        }
        return $attachments;
    }
    public static function uploadVideos(array $videos)
    {
        // TODO: Implement uploadVideos() method after.
        return $videos;
    }
    public static function uploadFiles(array $files)
    {
        $uploader = new self();

        $attachments = [];
        foreach ($files as $file) {
            $uniqueName = self::generateUniqueFileName($file->name);
            $attachment = [
                'type' => 'file',
                'original_name' => $file->name,
                'file_name' => $uniqueName,
                'file_path' => $file->tempName,
                'file_size' => $file->size,
                'mime_type' => $file->type,
            ];

            $targetPath = $uploader->uploadPath . $attachment['file_name'];
            if (move_uploaded_file($file->tempName, $targetPath)) {
                $attachment['file_path'] = '/uploads/chats/' . $attachment['file_name'];
            } else {
                throw new \Exception("Не удалось переместить файл: " . $file->name);
            }

            $attachments[] = $attachment;
        }
        return $attachments;
    }
}
