<?php

namespace app\services;

class ChatUploader
{
    //

    public static function uploadImages(array $images)
    {
        return [
            'images' => $images,
        ];
    }
    public static function uploadVideos(array $videos)
    {
        return [
            'videos' => $videos,
        ];
    }
    public static function uploadFiles(array $files)
    {
        return [
            'files' => $files,
        ];
    }
    public static function uploadAudios(array $audio)
    {
        return [
            'audio' => $audio,
        ];
    }
}
