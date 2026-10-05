<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Contracts\GatewayWebhooks;
use App\Modules\Payments\Jobs\ProcessGatewayEventJob;
use App\Modules\Payments\Models\PaymentGatewayEvent;
use Carbon\CarbonImmutable;

final readonly class WebhookReceiver implements GatewayWebhooks
{
    public function __construct(private GatewayRegistry $gateways) {}

    public function receive(string $gatewayKey, string $rawBody, array $headers): void
    {
        $event = $this->gateways->get($gatewayKey)->parseWebhook($rawBody, $headers);

        // Idempotente por id de evento: un reenvío de la pasarela no crea un segundo registro ni un segundo proceso.
        $stored = PaymentGatewayEvent::query()->firstOrCreate(
            ['gateway' => $gatewayKey, 'event_id' => $event->eventId],
            ['gateway_reference' => $event->gatewayReference, 'outcome' => $event->outcome, 'received_at' => CarbonImmutable::now()],
        );

        if ($stored->wasRecentlyCreated) {
            ProcessGatewayEventJob::dispatch($stored->id);
        }
    }
}
