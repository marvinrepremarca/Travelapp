<?php

declare(strict_types=1);

namespace App\Modules\Crm\Exceptions;

use App\Modules\Crm\Enums\CustomerType;
use App\Modules\Crm\Enums\DocumentType;
use App\Modules\Shared\Exceptions\BusinessRuleException;

final class CustomerRuleViolation extends BusinessRuleException
{
    private string $stableCode = '';

    /** No revela a quién pertenece el cliente existente (puede estar fuera del alcance). */
    public static function duplicateDocument(): self
    {
        return self::make('duplicate_document', __('crm.errors.duplicate_document'));
    }

    public static function consentRequired(): self
    {
        return self::make('consent_required', __('crm.errors.consent_required'));
    }

    public static function documentNotAllowed(CustomerType $type, DocumentType $document): self
    {
        return self::make('document_not_allowed', __('crm.errors.document_not_allowed', ['document' => $document->label(), 'type' => $type->label()]));
    }

    public static function sensitiveDataForbidden(): self
    {
        return self::make('sensitive_data_forbidden', __('crm.errors.sensitive_data_forbidden'));
    }

    public static function ownerNotAvailable(): self
    {
        return self::make('owner_not_available', __('crm.errors.owner_not_available'));
    }

    public function errorCode(): string
    {
        return $this->stableCode;
    }

    private static function make(string $code, string $message): self
    {
        $exception = new self($message);
        $exception->stableCode = $code;

        return $exception;
    }
}
