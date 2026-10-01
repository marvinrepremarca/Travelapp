<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Seguridad de la cuenta propia: activar, confirmar y desactivar 2FA, códigos de recuperación.
 * La ruta exige confirmar la contraseña (middleware password.confirm).
 */
#[Layout('components.layouts.backoffice')]
final class SecuritySettings extends Component
{
    public string $code = '';

    public bool $showRecoveryCodes = false;

    public function enable(EnableTwoFactorAuthentication $enable): void
    {
        $enable($this->user());
        $this->showRecoveryCodes = false;
    }

    public function confirm(ConfirmTwoFactorAuthentication $confirm): void
    {
        $this->validate(['code' => ['required', 'string']], attributes: ['code' => __('identity.fields.two_factor_code')]);

        $confirm($this->user(), $this->code);

        $this->reset('code');
        $this->showRecoveryCodes = true;
        session()->flash('status', __('identity.security.enabled'));
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generate): void
    {
        $generate($this->user());
        $this->showRecoveryCodes = true;
    }

    public function disable(DisableTwoFactorAuthentication $disable): void
    {
        if ($this->user()->requiresTwoFactor()) {
            $this->addError('two_factor', __('identity.security.required_cannot_disable'));

            return;
        }

        $disable($this->user());
        $this->showRecoveryCodes = false;
        session()->flash('status', __('identity.security.disabled'));
    }

    public function render(): View
    {
        // Las acciones de Fortify actualizan esta misma instancia: no hace falta recargarla (ni sus roles).
        $user = $this->user();
        $pending = $user->two_factor_secret !== null && ! $user->hasTwoFactorEnabled();

        return view('identity::livewire.security-settings', [
            'enabled' => $user->hasTwoFactorEnabled(),
            'pending' => $pending,
            'required' => $user->requiresTwoFactor(),
            'qrCode' => $pending ? base64_encode($user->twoFactorQrCodeSvg()) : null,
            'recoveryCodes' => $this->showRecoveryCodes && $user->two_factor_recovery_codes !== null ? $user->recoveryCodes() : [],
        ])->title(__('identity.security.title'))
            ->layoutData(['heading' => __('identity.security.title')]);
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
