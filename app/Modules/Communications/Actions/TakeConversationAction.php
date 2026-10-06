<?php

declare(strict_types=1);

namespace App\Modules\Communications\Actions;

use App\Modules\Communications\Enums\BotStep;
use App\Modules\Communications\Enums\ConversationStatus;
use App\Modules\Communications\Enums\MessageAuthor;
use App\Modules\Communications\Exceptions\ConversationRuleViolation;
use App\Modules\Communications\Models\Conversation;
use App\Modules\Communications\Services\MessageOutbox;
use App\Modules\Crm\Contracts\LeadIntake;
use App\Modules\Crm\Data\LeadData;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\SalesChannel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Un asesor toma la conversación: queda a su cargo, se crea el lead con lo que recogió el bot
 * (canal WhatsApp) y el cliente recibe un aviso de que lo atiende un asesor.
 */
final readonly class TakeConversationAction
{
    public function __construct(
        private LeadIntake $leads,
        private MessageOutbox $outbox,
    ) {}

    public function execute(User $agent, string $conversationUlid): Conversation
    {
        return DB::transaction(function () use ($agent, $conversationUlid): Conversation {
            $conversation = Conversation::query()->where('ulid', $conversationUlid)->lockForUpdate()->firstOrFail();
            if (! $conversation->status->isOpen()) {
                throw ConversationRuleViolation::closed();
            }

            if ($conversation->owner_id !== null) {
                throw ConversationRuleViolation::alreadyTaken();
            }

            $conversation->owner_id = $agent->id;
            $conversation->branch_id = $agent->branch_id;
            $conversation->status = ConversationStatus::WithAgent;
            $conversation->lead_ulid ??= $this->leads->register($this->leadData($conversation), $agent);
            $conversation->save();

            $this->outbox->queue($conversation, MessageAuthor::System, __('communications.agent_joined', ['agent' => $agent->name]));

            return $conversation;
        });
    }

    private function leadData(Conversation $conversation): LeadData
    {
        $data = $conversation->bot_data;
        $date = static fn(mixed $value): ?CarbonImmutable => is_string($value) ? CarbonImmutable::parse($value) : null;

        return new LeadData(
            contactName: $conversation->contact_name ?? __('communications.unknown_contact'),
            channel: SalesChannel::WhatsApp,
            phone: $conversation->contact_phone,
            destination: is_string($data[BotStep::Destination->value] ?? null) ? (string) $data[BotStep::Destination->value] : null,
            travelStart: $date($data[BotStep::Departure->value] ?? null),
            travelEnd: $date($data[BotStep::Return->value] ?? null),
            travelersCount: is_int($data[BotStep::Travelers->value] ?? null) ? (int) $data[BotStep::Travelers->value] : null,
            notes: __('communications.lead_notes'),
        );
    }
}
