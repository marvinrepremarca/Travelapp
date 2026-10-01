<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Quotes\Enums\AcceptanceChannel;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Events\QuoteAccepted;
use App\Modules\Quotes\Exceptions\QuoteRuleViolation;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteOption;
use App\Modules\Quotes\Models\QuoteVersion;
use App\Modules\Quotes\Services\QuoteLifecycle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Registra que el cliente acepta una opción de la versión vigente, solo antes de que venza.
 * Si ya venció, la marca como vencida y rechaza la aceptación.
 */
final readonly class AcceptQuoteAction
{
    public function __construct(private QuoteLifecycle $lifecycle) {}

    public function execute(Quote $quote, string $optionUlid, AcceptanceChannel $channel, ?string $note, CarbonImmutable $now): Quote
    {
        $expired = false;

        $accepted = DB::transaction(function () use ($quote, $optionUlid, $channel, $note, $now, &$expired): Quote {
            $quote = Quote::query()->whereKey($quote->id)->lockForUpdate()->firstOrFail();

            if ($quote->status === QuoteStatus::Sent && $quote->isPastValidity($now)) {
                $this->lifecycle->transition($quote, QuoteStatus::Expired);
                $quote->save();
                $expired = true;

                return $quote;
            }

            $this->lifecycle->transition($quote, QuoteStatus::Accepted);
            $option = $this->optionOfCurrentVersion($quote, $optionUlid);

            $quote->accepted_option_id = $option->id;
            $quote->accepted_version = $quote->current_version;
            $quote->accepted_at = $now;
            $quote->acceptance_channel = $channel;
            $quote->acceptance_note = $note;
            $quote->save();

            return $quote;
        });

        if ($expired) {
            throw QuoteRuleViolation::expired();
        }

        QuoteAccepted::dispatch($accepted->ulid, $optionUlid, (int) $accepted->accepted_version);

        return $accepted;
    }

    /** Solo se acepta lo que el cliente recibió: una opción presente en la versión enviada. */
    private function optionOfCurrentVersion(Quote $quote, string $optionUlid): QuoteOption
    {
        $version = QuoteVersion::query()->where('quote_id', $quote->id)->where('version', $quote->current_version)->firstOrFail();
        $sentOptionUlids = array_column($version->snapshot['options'], 'ulid');

        if (! in_array($optionUlid, $sentOptionUlids, true)) {
            throw QuoteRuleViolation::optionNotInVersion();
        }

        return QuoteOption::query()->where('quote_id', $quote->id)->where('ulid', $optionUlid)->firstOrFail();
    }
}
