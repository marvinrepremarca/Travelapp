<?php

declare(strict_types=1);

namespace App\Modules\Organization\Services;

use App\Modules\Organization\Contracts\AppSettings;
use App\Modules\Organization\Enums\SettingKey;
use App\Modules\Shared\Enums\VisibilityScope;

/** Parámetros de la agencia: valor editado en la administración o default de configuración. */
final readonly class DatabaseAppSettings implements AppSettings
{
    public function __construct(private SettingsStore $store) {}

    public function agencyTimezone(): string
    {
        return (string) $this->store->get(SettingKey::AgencyTimezone);
    }

    public function defaultCurrency(): string
    {
        return (string) $this->store->get(SettingKey::DefaultCurrency);
    }

    public function quoteValidityHours(): int
    {
        return (int) $this->store->get(SettingKey::QuoteValidityHours);
    }

    public function onRequestResponseSlaHours(): int
    {
        return (int) $this->store->get(SettingKey::OnRequestResponseSlaHours);
    }

    public function travelAgentScope(): VisibilityScope
    {
        return VisibilityScope::from((string) $this->store->get(SettingKey::TravelAgentScope));
    }

    public function hideMarginsFromAgents(): bool
    {
        return (bool) $this->store->get(SettingKey::HideMarginsFromAgents);
    }
}
