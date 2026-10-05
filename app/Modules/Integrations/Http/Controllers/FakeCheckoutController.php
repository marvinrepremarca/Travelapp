<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Http\Controllers;

use App\Modules\Integrations\Adapters\FakePayments\FakePaymentGateway;
use App\Modules\Payments\Contracts\GatewayWebhooks;
use App\Modules\Payments\Enums\PaymentStatus;
use Brick\Money\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Página de pago de la pasarela simulada (solo con enlace firmado). Al elegir el resultado, entrega a la app
 * un webhook firmado por el mismo camino que usaría una pasarela real.
 */
final class FakeCheckoutController
{
    public function show(Request $request): View
    {
        return view('integrations::fake-checkout', [
            'reference' => (string) $request->query('reference'),
            'amount' => Money::ofMinor((int) $request->query('amount'), (string) $request->query('currency')),
            'completed' => null,
        ]);
    }

    public function complete(Request $request, GatewayWebhooks $webhooks): View
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:191'],
            'amount' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'outcome' => ['required', Rule::in([PaymentStatus::Approved->value, PaymentStatus::Rejected->value])],
        ]);

        $body = json_encode(['event_id' => (string) Str::uuid(), 'reference' => $data['reference'], 'outcome' => $data['outcome']], JSON_THROW_ON_ERROR);
        $webhooks->receive(FakePaymentGateway::KEY, $body, [FakePaymentGateway::SIGNATURE_HEADER => FakePaymentGateway::sign($body)]);

        return view('integrations::fake-checkout', [
            'reference' => $data['reference'],
            'amount' => Money::ofMinor((int) $data['amount'], $data['currency']),
            'completed' => PaymentStatus::from($data['outcome']),
        ]);
    }
}
