<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Exceptions;

use App\Modules\Integrations\Enums\RequestOutcome;
use App\Modules\Shared\Exceptions\BusinessRuleException;

final class ProviderRequestFailed extends BusinessRuleException
{
    public RequestOutcome $outcome = RequestOutcome::InvalidResponse;

    public static function because(string $provider, RequestOutcome $outcome): self
    {
        $exception = new self(__('integrations.errors.request_failed', ['provider' => $provider]));
        $exception->outcome = $outcome;

        return $exception;
    }

    public static function hostNotAllowed(string $host): self
    {
        return new self(__('integrations.errors.host_not_allowed', ['host' => $host]));
    }

    public function errorCode(): string
    {
        return 'provider_request_failed';
    }
}
