<?php

declare(strict_types=1);

namespace App\Modules\Crm\Data;

use App\Modules\Crm\Enums\ConsentChannel;

final readonly class ConsentData
{
    public function __construct(
        public bool $dataProcessing,
        public bool $marketing,
        public ConsentChannel $channel,
    ) {}
}
