<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use App\Modules\Identity\Auth\PasswordValidationRules;
use App\Modules\Identity\Models\User;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Primer ingreso: el usuario define su propia contraseña para reemplazar la temporal. */
#[Layout('components.layouts.backoffice')]
final class ChangeTemporaryPassword extends Component
{
    use PasswordValidationRules;

    public string $password = '';

    public string $password_confirmation = '';

    public function save(): void
    {
        $user = $this->actor();
        $this->validate();

        $user->forceFill([
            'password' => $this->password,
            'must_change_password' => false,
            'temporary_password' => null,
        ])->save();

        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render(): View
    {
        return view('identity::livewire.change-temporary-password')
            ->title(__('identity.password_change.title'))
            ->layoutData(['heading' => __('identity.password_change.title')]);
    }

    /** @return array<string, list<mixed>> */
    protected function rules(): array
    {
        return [
            'password' => [...$this->passwordRules(), $this->differentFromCurrent()],
        ];
    }

    /** La nueva contraseña no puede ser la temporal que se le entregó. */
    private function differentFromCurrent(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && Hash::check($value, $this->actor()->password)) {
                $fail(__('identity.password_change.must_differ'));
            }
        };
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
