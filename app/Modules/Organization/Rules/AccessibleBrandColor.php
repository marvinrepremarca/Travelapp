<?php

declare(strict_types=1);

namespace App\Modules\Organization\Rules;

use App\Modules\Shared\Accessibility\ContrastRatio;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Color de marca en #rrggbb con contraste AA frente al texto que se pinta encima. */
final readonly class AccessibleBrandColor implements ValidationRule
{
    public function __construct(private string $textColor) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! ContrastRatio::isHex($value)) {
            $fail(__('organization.errors.brand_color_format'));

            return;
        }

        if (! ContrastRatio::meetsAa($this->textColor, $value)) {
            $fail(__('organization.errors.brand_color_contrast', [
                'ratio' => number_format(ContrastRatio::between($this->textColor, $value), 2),
                'minimum' => ContrastRatio::AA_NORMAL_TEXT,
            ]));
        }
    }
}
