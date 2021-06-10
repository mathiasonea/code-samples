<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUsername
{
    public static function generateUsername(string $firstName, string $lastName): string
    {
        return Str::of($firstName)->lower()->substr(0,1)
            .Str::of($lastName)->slug('');
    }
}