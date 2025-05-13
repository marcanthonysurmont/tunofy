<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class AvatarHelper
{
    public static function generateAvatarUrl(object $object, string $attribute, string $defaultImage = 'images/default-avatar.jpg'): string
    {
        $path = $object->$attribute ?? null;

        if ($path) {
            return $path;
        }

        return asset($defaultImage);
    }

    public static function getDefaultAvatarUrl(): string
    {
        return asset('images/default-avatar.jpg');
    }
}