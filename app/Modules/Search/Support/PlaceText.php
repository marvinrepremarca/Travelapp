<?php

declare(strict_types=1);

namespace App\Modules\Search\Support;

use Illuminate\Support\Str;

/** Normaliza nombres de lugares para comparar sin tildes ni mayúsculas ("Bogotá" = "bogota"). */
final class PlaceText
{
    public static function normalize(string $text): string
    {
        return Str::of($text)->ascii()->lower()->squish()->value();
    }
}
