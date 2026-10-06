<?php

declare(strict_types=1);

namespace App\Modules\Communications\Exceptions;

use App\Modules\Shared\Exceptions\BusinessRuleException;

/** Webhook entrante rechazado (firma inválida, formato desconocido o canal no registrado). */
final class InvalidInboundMessage extends BusinessRuleException
{
    private string $reason = '';

    public static function because(string $reason): self
    {
        $exception = new self(__('communications.errors.invalid_inbound', ['reason' => $reason]));
        $exception->reason = $reason;

        return $exception;
    }

    public function errorCode(): string
    {
        return 'invalid_inbound_' . $this->reason;
    }
}
