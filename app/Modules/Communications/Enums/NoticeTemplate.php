<?php

declare(strict_types=1);

namespace App\Modules\Communications\Enums;

/**
 * Plantillas de avisos iniciados por la agencia. En WhatsApp real cada una debe estar aprobada por Meta con este nombre.
 */
enum NoticeTemplate: string
{
    case QuoteSent = 'quote_sent';
    case PaymentLink = 'payment_link';
    case PaymentReceived = 'payment_received';
    case BalanceReminder = 'balance_reminder';

    /** @param  array<string, string>  $params */
    public function render(array $params): string
    {
        return __("communications.templates.{$this->value}", $params);
    }
}
