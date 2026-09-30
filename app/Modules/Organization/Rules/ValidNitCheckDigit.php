<?php

declare(strict_types=1);

namespace App\Modules\Organization\Rules;

use App\Modules\Organization\Services\NitCheckDigit;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** El dígito de verificación digitado debe coincidir con el calculado para el NIT. */
final readonly class ValidNitCheckDigit implements ValidationRule
{
    public function __construct(private string $nit) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! ctype_digit($this->nit) || (string) NitCheckDigit::for($this->nit) !== (string) $value) {
            $fail(__('organization.errors.nit_check_digit_mismatch'));
        }
    }
}
