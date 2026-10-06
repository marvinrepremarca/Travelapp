<?php

declare(strict_types=1);

namespace App\Modules\Reports\Data;

use Carbon\CarbonImmutable;

/**
 * Mes de reporte con corte en la zona horaria de la agencia. `from`/`until` son instantes UTC (until exclusivo).
 */
final readonly class Period
{
    private const MONTH_PATTERN = '/^\d{4}-(0[1-9]|1[0-2])$/';

    private const MONTH_FORMAT = 'Y-m';

    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}

    /** "2026-10" en la zona de la agencia; el mes actual si el texto no es válido. */
    public static function month(string $month, CarbonImmutable $now): self
    {
        $timezone = config()->string('travel.agency.timezone');
        $start = preg_match(self::MONTH_PATTERN, $month) === 1
            ? CarbonImmutable::createFromFormat('!' . self::MONTH_FORMAT, $month, $timezone)
            : null;
        $start = $start instanceof CarbonImmutable ? $start : $now->setTimezone($timezone)->startOfMonth();

        return new self($start->startOfMonth(), $start->startOfMonth()->addMonth());
    }

    public function previous(): self
    {
        return new self($this->start->subMonth(), $this->start);
    }

    public function from(): CarbonImmutable
    {
        return $this->start->utc();
    }

    public function until(): CarbonImmutable
    {
        return $this->end->utc();
    }

    public function key(): string
    {
        return $this->start->format(self::MONTH_FORMAT);
    }

    public function label(): string
    {
        return $this->start->translatedFormat(config()->string('travel.reports.month_label_format'));
    }

    /** Días del mes para series diarias. */
    public function days(): int
    {
        return (int) $this->start->diffInDays($this->end);
    }
}
