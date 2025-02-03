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
    public static function uploadImages(array $images)
    {
        $uploader = new self();

        $attachments = [];
        foreach ($images as $image) {
            $attachment = new ChatAttachment();
            $attachment->type = 'image';
            $attachment->file_name = $image->name;
            $attachment->file_path = $image->tempName;
            $attachment->file_size = $image->size;
            $attachment->mime_type = $image->type;

            $targetPath = $uploader->uploadPath . $attachment->file_name;
            if (move_uploaded_file($image->tempName, $targetPath)) {
                $attachment->file_path = $targetPath;
            } else {
                throw new \Exception("Не удалось переместить файл: " . $image->name);
            }

            $imagick = new Imagick($attachment->file_path);
            $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
            $imagick->setImageCompressionQuality(75);
            $imagick->stripImage();
            $imagick->writeImage($attachment->file_path);

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
        return $audio;
    }
}
