<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Exceptions;

use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Shared\Exceptions\BusinessRuleException;

final class QuoteRuleViolation extends BusinessRuleException
{
    private string $stableCode = '';

    public static function notEditable(QuoteStatus $status): self
    {
        return self::make('not_editable', __('quotes.errors.not_editable', ['status' => $status->label()]));
    }

    public static function invalidTransition(QuoteStatus $from, QuoteStatus $to): self
    {
        return self::make('invalid_transition', __('quotes.errors.invalid_transition', ['from' => $from->label(), 'to' => $to->label()]));
    }

    public static function emptyOption(string $label): self
    {
        return self::make('empty_option', __('quotes.errors.empty_option', ['label' => $label]));
    }

    public static function tooManyOptions(int $max): self
    {
        return self::make('too_many_options', __('quotes.errors.too_many_options', ['max' => $max]));
    }

    public static function expired(): self
    {
        return self::make('expired', __('quotes.errors.expired'));
    }

    public static function optionNotInVersion(): self
    {
        return self::make('option_not_in_version', __('quotes.errors.option_not_in_version'));
    }

    public static function manualNetRequired(): self
    {
        return self::make('manual_net_required', __('quotes.errors.manual_net_required'));
    }

    public static function lastOption(): self
    {
        return self::make('last_option', __('quotes.errors.last_option'));
    }

    public static function notAccepted(): self
    {
        return self::make('not_accepted', __('quotes.errors.not_accepted'));
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
