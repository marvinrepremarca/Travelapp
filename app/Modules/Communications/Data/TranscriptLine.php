<?php

declare(strict_types=1);

namespace App\Modules\Communications\Data;

use App\Modules\Communications\Enums\MessageAuthor;
use App\Modules\Communications\Enums\MessageDirection;
use App\Modules\Communications\Enums\MessageStatus;
use Carbon\CarbonImmutable;

final readonly class TranscriptLine
{
    public function __construct(
        public string $ulid,
        public MessageDirection $direction,
        public MessageAuthor $author,
        public string $body,
        public MessageStatus $status,
        public CarbonImmutable $at,
    ) {}
}
