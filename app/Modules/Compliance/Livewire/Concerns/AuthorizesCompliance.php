<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Livewire\Concerns;

use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;

trait AuthorizesCompliance
{
    protected function authorizeCompliance(): void
    {
        abort_unless($this->actor()->can(Permission::ComplianceManage->value), 403);
    }

    protected function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    protected function today(): CarbonImmutable
    {
        // Fecha local de la agencia como fecha pura (medianoche), comparable con las columnas `date`.
        return CarbonImmutable::parse(CarbonImmutable::now(config()->string('travel.agency.timezone'))->toDateString());
    }

    /**
     * Usuarios activos que pueden ser responsables.
     *
     * @return array<int, string>
     */
    protected function responsibles(): array
    {
        return User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all();
    }
}
