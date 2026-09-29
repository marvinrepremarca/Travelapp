<?php

declare(strict_types=1);

namespace App\Modules\Identity\Auth;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /** @return list<mixed> */
    protected function passwordRules(): array
    {
        return ['required', 'string', Password::default(), 'confirmed'];
    }
}
