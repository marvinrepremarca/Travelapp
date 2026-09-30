<?php

declare(strict_types=1);

namespace App\Modules\Organization\Enums;

use App\Modules\Shared\Enums\VisibilityScope;
use Illuminate\Validation\Rule;

/**
 * Parámetros de negocio ⚙ editables desde la administración.
 * Cada clave tiene su default en config/travel.php, su tipo y sus reglas de validación.
 */
enum SettingKey: string
{
    case AgencyTimezone = 'agency.timezone';
    case DefaultCurrency = 'agency.default_currency';
    case QuoteValidityHours = 'quotes.validity_hours';
    case OnRequestResponseSlaHours = 'bookings.on_request_response_sla_hours';
    case TravelAgentScope = 'visibility.travel_agent_scope';
    case HideMarginsFromAgents = 'visibility.hide_margins_from_agents';

    private const MAX_HOURS = 720;

    public function configPath(): string
    {
        return 'travel.' . $this->value;
    }

    public function type(): SettingType
    {
        return match ($this) {
            self::QuoteValidityHours, self::OnRequestResponseSlaHours => SettingType::Integer,
            self::HideMarginsFromAgents => SettingType::Boolean,
            self::AgencyTimezone, self::DefaultCurrency, self::TravelAgentScope => SettingType::String,
        };
    }

    /** @return list<mixed> */
    public function rules(): array
    {
        return match ($this) {
            self::AgencyTimezone => ['required', 'timezone:all'],
            self::DefaultCurrency => ['required', 'string', 'size:3', 'uppercase'],
            self::QuoteValidityHours, self::OnRequestResponseSlaHours => ['required', 'integer', 'min:1', 'max:' . self::MAX_HOURS],
            self::TravelAgentScope => ['required', Rule::in([VisibilityScope::Own->value, VisibilityScope::Branch->value])],
            self::HideMarginsFromAgents => ['required', 'boolean'],
        };
    }

    public function label(): string
    {
        return __("organization.settings.{$this->value}.label");
    }

    public function help(): string
    {
        return __("organization.settings.{$this->value}.help");
    }

    /** Nombre de campo seguro para formularios (sin puntos). */
    public function field(): string
    {
        return str_replace('.', '__', $this->value);
    }
}
