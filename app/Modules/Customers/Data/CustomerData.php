<?php

declare(strict_types=1);

namespace App\Modules\Customers\Data;

use App\Modules\Customers\Enums\CustomerType;
use App\Modules\Customers\Enums\DocumentType;
use Carbon\CarbonImmutable;

final readonly class CustomerData
{
    public function __construct(
        public CustomerType $type,
        public DocumentType $documentType,
        public string $documentNumber,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $legalName = null,
        public ?CarbonImmutable $birthDate = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $city = null,
        public ?string $country = null,
        public ?string $notes = null,
    ) {}

    public function displayName(): string
    {
        return $this->type === CustomerType::Company
            ? (string) $this->legalName
            : trim($this->firstName . ' ' . $this->lastName);
    }
}
