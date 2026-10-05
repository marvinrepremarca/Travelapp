<?php

declare(strict_types=1);

namespace App\Modules\Search\Enums;

/** Régimen de alimentación normalizado; cada adaptador mapea sus códigos y lo desconocido va a `Unknown`. */
enum BoardType: string
{
    case RoomOnly = 'room_only';
    case Breakfast = 'breakfast';
    case HalfBoard = 'half_board';
    case FullBoard = 'full_board';
    case AllInclusive = 'all_inclusive';
    case Unknown = 'unknown';

    public function label(): string
    {
        return __("search.board.{$this->value}");
    }
}
