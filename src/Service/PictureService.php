<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class PictureService
{
    public function __construct(private string $uploadDir) {}

    public function uploadImage(UploadedFile $file): string
    {
        $filename = uniqid() . '.' . $file->guessExtension();

        $file->move($this->uploadDir, $filename);

        return '/uploads/events/' . $filename;
    }
}