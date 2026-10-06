<?php

declare(strict_types=1);

namespace App\Modules\Communications\Services;

use App\Modules\Communications\Data\BotTurn;
use App\Modules\Communications\Enums\BotStep;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Bot guiado de cotización: pregunta nombre, destino, fechas y número de viajeros; valida cada respuesta
 * y, al terminar (o si el cliente pide un asesor), deja la conversación lista para que un asesor cotice.
 * Sin estado propio: recibe el paso y los datos de la conversación y devuelve el siguiente turno.
 */
final class GuidedQuoteBot
{
    private const MAX_TEXT = 120;

    private const NON_DIGITS = '/\D+/';

    public function welcome(): BotTurn
    {
        return new BotTurn(__('communications.bot.welcome') . "\n\n" . BotStep::Name->prompt(), BotStep::Name, [], false);
    }

    /** @param  array<string, string|int|null>  $data */
    public function answer(BotStep $step, array $data, string $text, CarbonImmutable $today): BotTurn
    {
        $text = trim($text);
        if ($this->asksForAgent($text)) {
            return new BotTurn(__('communications.bot.handoff'), BotStep::Done, $data, true);
        }

        try {
            $data[$step->value] = $this->parse($step, $text, $data, $today);
        } catch (InvalidArgumentException $invalid) {
            return new BotTurn($invalid->getMessage() . "\n" . $step->prompt(), $step, $data, false);
        }

        $next = $step->next();
        if ($next === BotStep::Done) {
            return new BotTurn(__('communications.bot.summary', $this->summary($data)), BotStep::Done, $data, true);
        }

        return new BotTurn($next->prompt(), $next, $data, false);
    }

    /** @param  array<string, string|int|null>  $data */
    private function parse(BotStep $step, string $text, array $data, CarbonImmutable $today): string|int|null
    {
        return match ($step) {
            BotStep::Name, BotStep::Destination => $this->text($text),
            BotStep::Departure => $this->date($text, $today)->toDateString(),
            BotStep::Return => $this->returnDate($text, CarbonImmutable::parse((string) ($data[BotStep::Departure->value] ?? $today->toDateString()))),
            BotStep::Travelers => $this->travelers($text),
            BotStep::Done => null,
        };
    }

    private function text(string $text): string
    {
        if ($text === '') {
            throw new InvalidArgumentException(__('communications.bot.invalid.text'));
        }

        return Str::limit($text, self::MAX_TEXT, '');
    }

    private function date(string $text, CarbonImmutable $today): CarbonImmutable
    {
        $date = $this->parseDate($text);
        if (! $date instanceof CarbonImmutable || $date->lessThan($today->startOfDay())) {
            throw new InvalidArgumentException(__('communications.bot.invalid.date', ['example' => $today->addMonth()->format(config()->string('travel.communications.bot_date_format'))]));
        }

        return $date;
    }

    /** "No" = solo ida. */
    private function returnDate(string $text, CarbonImmutable $departure): ?string
    {
        if (in_array(Str::of($text)->ascii()->lower()->value(), $this->words('one_way_words'), true)) {
            return null;
        }

        $date = $this->parseDate($text);
        if (! $date instanceof CarbonImmutable || $date->lessThan($departure)) {
            throw new InvalidArgumentException(__('communications.bot.invalid.return'));
        }

        return $date->toDateString();
    }

    private function travelers(string $text): int
    {
        $count = (int) preg_replace(self::NON_DIGITS, '', $text);
        if ($count < 1 || $count > config()->integer('travel.search.max_passengers')) {
            throw new InvalidArgumentException(__('communications.bot.invalid.travelers', ['max' => config()->integer('travel.search.max_passengers')]));
        }

        return $count;
    }

    private function parseDate(string $text): ?CarbonImmutable
    {
        try {
            $date = CarbonImmutable::createFromFormat('!' . config()->string('travel.communications.bot_date_format'), trim($text));
        } catch (\Carbon\Exceptions\InvalidFormatException) {
            return null;
        }

        return $date instanceof CarbonImmutable ? $date : null;
    }

    private function asksForAgent(string $text): bool
    {
        return in_array(Str::of($text)->ascii()->lower()->value(), $this->words('agent_words'), true);
    }

    /** @return list<string> */
    private function words(string $key): array
    {
        /** @var list<string> $words */
        $words = config()->array("travel.communications.{$key}");

        return $words;
    }

    /**
     * @param  array<string, string|int|null>  $data
     * @return array<string, string>
     */
    private function summary(array $data): array
    {
        $format = config()->string('travel.communications.bot_date_format');
        $date = static fn(mixed $value): string => is_string($value) ? CarbonImmutable::parse($value)->format($format) : __('communications.bot.one_way');

        return [
            'name' => (string) ($data[BotStep::Name->value] ?? ''),
            'destination' => (string) ($data[BotStep::Destination->value] ?? ''),
            'departure' => $date($data[BotStep::Departure->value] ?? null),
            'return' => $date($data[BotStep::Return->value] ?? null),
            'travelers' => (string) ($data[BotStep::Travelers->value] ?? ''),
        ];
    }
}
