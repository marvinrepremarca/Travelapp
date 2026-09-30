<?php

declare(strict_types=1);

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Data\CustomerData;
use App\Modules\Crm\Exceptions\CustomerRuleViolation;
use App\Modules\Crm\Models\Customer;

/** Valida el documento de un cliente y calcula su huella para unicidad y búsqueda. */
final readonly class CustomerDocumentGuard
{
    public const HASH_SCOPE = 'customer_document';

    public function __construct(private PersonalDataHasher $hasher) {}

    /** @return array{0: string, 1: string} número normalizado y huella */
    public function check(CustomerData $data, ?Customer $existing = null): array
    {
        if (! in_array($data->documentType, $data->type->allowedDocuments(), true)) {
            throw CustomerRuleViolation::documentNotAllowed($data->type, $data->documentType);
        }

        $number = $data->documentType->normalize($data->documentNumber);
        $hash = $this->hasher->hash(self::HASH_SCOPE . ':' . $data->documentType->value, $number);

        $duplicate = Customer::withTrashed()
            ->where('document_type', $data->documentType)
            ->where('document_hash', $hash)
            ->when($existing instanceof \App\Modules\Crm\Models\Customer, static fn($query) => $query->whereKeyNot($existing?->id))
            ->exists();

        if ($duplicate) {
            throw CustomerRuleViolation::duplicateDocument();
        }

        return [$number, $hash];
    }

    public function hashFor(string $documentTypeValue, string $normalizedNumber): string
    {
        return $this->hasher->hash(self::HASH_SCOPE . ':' . $documentTypeValue, $normalizedNumber);
    }
}
