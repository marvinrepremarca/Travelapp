<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Data;

use App\Modules\Suppliers\Enums\PaymentTerms;
use Carbon\CarbonImmutable;

final readonly class SupplierData
{
    public function __construct(
        public string $legalName,
        public string $tradeName,
        public string $taxId,
        public string $country,
        public bool $isTourismProvider,
        public PaymentTerms $paymentTerms,
        public int $paymentDays,
        public string $paymentCurrency,
        public ?string $rntNumber = null,
        public ?CarbonImmutable $rntExpiresOn = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $website = null,
        public ?string $notes = null,
    ) {}
}
