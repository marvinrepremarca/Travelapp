<?php

declare(strict_types=1);

namespace App\Modules\Payments\Jobs;

use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentGatewayEvent;
use App\Modules\Shared\Enums\QueueName;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Aplica el resultado de la pasarela al pago pendiente. Un pago ya resuelto no cambia (eventos tardíos o repetidos). */
final class ProcessGatewayEventJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $eventId)
    {
        $this->onQueue(QueueName::Payments->value);
    }

    public function handle(): void
    {
        DB::transaction(function (): void {
            $event = PaymentGatewayEvent::query()->whereKey($this->eventId)->lockForUpdate()->firstOrFail();
            if ($event->processed_at !== null) {
                return;
            }

            $payment = Payment::query()->where('gateway_reference', $event->gateway_reference)->lockForUpdate()->first();
            if (! $payment instanceof Payment) {
                Log::warning('payment_event_without_payment', ['gateway' => $event->gateway, 'event_id' => $event->event_id]);
            } elseif ($payment->status === PaymentStatus::Pending) {
                $payment->status = $event->outcome;
                $payment->approved_at = $event->outcome === PaymentStatus::Approved ? CarbonImmutable::now() : null;
                $payment->save();
            }

            $event->processed_at = CarbonImmutable::now();
            $event->save();
        });
    }
}
