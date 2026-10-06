<?php

declare(strict_types=1);

namespace App\Modules\Communications\Livewire;

use App\Modules\Communications\Actions\CloseConversationAction;
use App\Modules\Communications\Actions\SendAgentReplyAction;
use App\Modules\Communications\Actions\TakeConversationAction;
use App\Modules\Communications\Enums\BotStep;
use App\Modules\Communications\Enums\ConversationStatus;
use App\Modules\Communications\Models\Conversation;
use App\Modules\Communications\Services\PhoneNumbers;
use App\Modules\Crm\Contracts\LeadIntake;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\SalesChannel;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Bandeja de WhatsApp: conversaciones que el bot guiado dejó listas para un asesor, las propias y el historial.
 * El asesor toma la conversación (se crea el lead), responde y la cierra.
 */
#[Layout('components.layouts.backoffice')]
final class ConversationsInbox extends Component
{
    public const FILTER_WAITING = 'waiting';

    public const FILTER_MINE = 'mine';

    public const FILTER_OPEN = 'open';

    public const FILTER_CLOSED = 'closed';

    public const FILTERS = [self::FILTER_WAITING, self::FILTER_MINE, self::FILTER_OPEN, self::FILTER_CLOSED];

    #[Url(except: self::FILTER_WAITING)]
    public string $filter = self::FILTER_WAITING;

    #[Url(except: '')]
    public string $conversation = '';

    public string $reply = '';

    public function select(string $ulid): void
    {
        $this->conversation = $ulid;
        $this->reset('reply');
        $this->resetErrorBag();
    }

    public function take(TakeConversationAction $take): void
    {
        $this->attempt(fn(): \App\Modules\Communications\Models\Conversation => $take->execute($this->actor(), $this->selected()->ulid));
    }

    public function send(SendAgentReplyAction $send): void
    {
        $this->validate(['reply' => ['required', 'string', 'max:' . config()->integer('travel.communications.max_message_length')]], attributes: ['reply' => __('communications.reply')]);

        $this->attempt(function () use ($send): void {
            $send->execute($this->actor(), $this->selected()->ulid, $this->reply);
            $this->reset('reply');
        });
    }

    /** Del chat a la cotización: con cliente vinculado va directo; si no, se crea el cliente desde el lead. */
    public function quote(LeadIntake $leads): void
    {
        $conversation = $this->selected();
        if ($conversation->owner_id !== $this->actor()->id || $conversation->lead_ulid === null) {
            $this->addError('conversation', __('communications.errors.take_first'));

            return;
        }

        $customerUlid = $leads->customerOf($conversation->lead_ulid);
        if ($customerUlid === null) {
            $this->redirectRoute('crm.customers.create', ['lead' => $conversation->lead_ulid], navigate: true);

            return;
        }

        $destination = $conversation->bot_data[BotStep::Destination->value] ?? null;
        $this->redirectRoute('quotes.create', [
            'customer' => $customerUlid,
            'title' => is_string($destination) && $destination !== '' ? __('crm.leads.quote_title', ['destination' => $destination]) : __('crm.leads.quote_title_generic', ['name' => (string) $conversation->contact_name]),
            'channel' => SalesChannel::WhatsApp->value,
        ], navigate: true);
    }

    public function close(CloseConversationAction $close): void
    {
        $this->attempt(fn(): \App\Modules\Communications\Models\Conversation => $close->execute($this->actor(), $this->selected()->ulid));
    }

    public function render(PhoneNumbers $phones): View
    {
        $selected = $this->conversation === '' ? null : Conversation::query()->inboxFor($this->actor())->with('messages')->where('ulid', $this->conversation)->first();

        return view('communications::livewire.conversations-inbox', [
            'conversations' => $this->list(),
            'selected' => $selected,
            'phones' => $phones,
            'filters' => self::FILTERS,
            'steps' => [BotStep::Name, BotStep::Destination, BotStep::Departure, BotStep::Return, BotStep::Travelers],
            'actor' => $this->actor(),
            'pollSeconds' => config()->integer('travel.communications.inbox_poll_seconds'),
            'timezone' => config()->string('travel.agency.timezone'),
        ])->title(__('communications.inbox_title'))
            ->layoutData(['heading' => __('communications.inbox_title')]);
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Conversation> */
    private function list(): \Illuminate\Database\Eloquent\Collection
    {
        return Conversation::query()
            ->inboxFor($this->actor())
            ->when($this->filter === self::FILTER_WAITING, static fn(Builder $query) => $query->whereNull('owner_id')->whereIn('status', [ConversationStatus::Bot, ConversationStatus::WaitingAgent]))
            ->when($this->filter === self::FILTER_MINE, fn(Builder $query) => $query->where('owner_id', $this->actor()->id)->where('status', '!=', ConversationStatus::Closed))
            ->when($this->filter === self::FILTER_OPEN, static fn(Builder $query) => $query->where('status', '!=', ConversationStatus::Closed))
            ->when($this->filter === self::FILTER_CLOSED, static fn(Builder $query) => $query->where('status', ConversationStatus::Closed))
            ->latest('last_message_at')
            ->limit(config()->integer('travel.communications.inbox_size'))
            ->get(['id', 'ulid', 'contact_name', 'contact_phone', 'status', 'owner_id', 'last_message_at']);
    }

    private function selected(): Conversation
    {
        $conversation = Conversation::query()->where('ulid', $this->conversation)->firstOrFail();
        abort_unless($conversation->isAvailableTo($this->actor()), 404);

        return $conversation;
    }

    private function attempt(callable $operation): void
    {
        try {
            $operation();
        } catch (BusinessRuleException $violation) {
            $this->addError('conversation', $violation->getMessage());
        }
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
