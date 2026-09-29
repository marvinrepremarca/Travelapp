<?php

declare(strict_types=1);

namespace App\Modules\Organization\Data;

use App\Modules\Organization\Enums\HolidaySource;
use Carbon\CarbonImmutable;

final readonly class Holiday
{
    public function __construct(
        public CarbonImmutable $date,
        public string $name,
        public HolidaySource $source,
    ) {}
}
