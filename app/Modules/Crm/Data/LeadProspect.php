<?php

declare(strict_types=1);

namespace App\Modules\Crm\Data;

use App\Modules\Shared\Enums\SalesChannel;

/** Contacto de un lead para dirigirle una cotización; `customerUlid` si ya está ligado a un cliente. */
final readonly class LeadProspect
{
    public function __construct(
        public string $leadUlid,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $customerUlid,
        public string $quoteTitle,
        public SalesChannel $channel,
    ) {}
}
