<?php

declare(strict_types=1);

namespace App\Modules\Communications\Data;

final readonly class SendResult
{
    public function __construct(
        public bool $accepted,
        public ?string $providerMessageId = null,
        public ?string $error = null,
    ) {}
}
