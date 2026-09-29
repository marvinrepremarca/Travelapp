<?php

declare(strict_types=1);

namespace App\Modules\Organization\Services;

use App\Modules\Organization\Contracts\AppSettings;
use Illuminate\Contracts\Config\Repository;

/** Implementación basada en `config/travel.php`. La tabla `settings` editable llega en la Fase 1.1. */
final readonly class ConfigAppSettings implements AppSettings
{
    public function __construct(private Repository $config) {}

    public function agencyTimezone(): string
    {
        return $this->config->string('travel.agency.timezone');
    }

    public function defaultCurrency(): string
    {
        return $this->config->string('travel.agency.default_currency');
    }

    public function quoteValidityHours(): int
    {
        return $this->config->integer('travel.quotes.validity_hours');
    }
}
