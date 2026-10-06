<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Livewire;

use App\Modules\Communications\Contracts\ConversationTranscripts;
use App\Modules\Communications\Contracts\InboundMessages;
use App\Modules\Integrations\Adapters\FakeWhatsApp\FakeWhatsAppChannel;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Simulador de WhatsApp de la demo: escribe como si fueras el cliente. Cada mensaje entra por el mismo
 * webhook firmado que usaría el proveedor real, así que el sistema no sabe que es una simulación.
 */
#[Layout('components.layouts.backoffice')]
final class WhatsAppSimulator extends Component
{
    #[Url(except: '')]
    public string $phone = '';

    public string $name = '';

    public string $text = '';

    public function mount(): void
    {
        abort_unless(config()->boolean('travel.communications.simulator_enabled'), 404);
        $this->phone = $this->phone !== '' ? $this->phone : config()->string('travel.communications.simulator_default_phone');
    }

    public function send(InboundMessages $inbound, FakeWhatsAppChannel $channel): void
    {
        abort_unless(config()->boolean('travel.communications.simulator_enabled'), 404);
        $this->validate([
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ]{7,20}$/'],
            'name' => ['nullable', 'string', 'max:100'],
            'text' => ['required', 'string', 'max:' . config()->integer('travel.communications.max_message_length')],
        ], attributes: ['phone' => __('integrations.whatsapp.phone'), 'name' => __('integrations.whatsapp.name'), 'text' => __('integrations.whatsapp.text')]);

        $webhook = $channel->webhookFor($this->phone, $this->text, $this->name !== '' ? $this->name : null);
        $inbound->accept(FakeWhatsAppChannel::KEY, $webhook['body'], $webhook['headers']);
        $this->reset('text');
    }

    public function render(ConversationTranscripts $transcripts): View
    {
        return view('integrations::livewire.whatsapp-simulator', [
            'lines' => $this->phone === '' ? [] : $transcripts->forPhone($this->phone, config()->integer('travel.communications.simulator_history')),
            'pollSeconds' => config()->integer('travel.communications.inbox_poll_seconds'),
            'timezone' => config()->string('travel.agency.timezone'),
        ])->title(__('integrations.whatsapp.title'))
            ->layoutData(['heading' => __('integrations.whatsapp.title')]);
    }
}
