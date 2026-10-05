<?php

declare(strict_types=1);

namespace App\Modules\Payments\Exceptions;

use App\Modules\Shared\Exceptions\BusinessRuleException;

final class PaymentRuleViolation extends BusinessRuleException
{
    private string $stableCode = '';

    public static function exceedsBalance(string $balance): self
    {
        return self::make('exceeds_balance', __('payments.errors.exceeds_balance', ['balance' => $balance]));
    }

    public static function notPending(): self
    {
        return self::make('not_pending', __('payments.errors.not_pending'));
    }

    public static function invalidSignature(): self
    {
        return self::make('invalid_signature', __('payments.errors.invalid_signature'));
    }

    public static function malformedWebhook(): self
    {
        return self::make('malformed_webhook', __('payments.errors.malformed_webhook'));
    }

    public static function unknownGateway(string $gateway): self
    {
        return self::make('unknown_gateway', __('payments.errors.unknown_gateway', ['gateway' => $gateway]));
    }

    public static function nothingToCollect(): self
    {
        return self::make('nothing_to_collect', __('payments.errors.nothing_to_collect'));
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
