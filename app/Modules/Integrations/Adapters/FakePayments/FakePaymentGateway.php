<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Adapters\FakePayments;

use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Data\GatewayEvent;
use App\Modules\Payments\Data\PaymentLink;
use App\Modules\Payments\Data\PaymentLinkRequest;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Exceptions\PaymentRuleViolation;
use Illuminate\Support\Facades\URL;
use JsonException;

/**
 * Pasarela simulada (sin red): el link lleva a una página de pago firmada de la propia app donde se elige aprobar o rechazar,
 * y la página entrega un webhook firmado con HMAC-SHA256, igual que haría una pasarela real.
 */
final class FakePaymentGateway implements PaymentGateway
{
    public const KEY = 'fake';

    public const SIGNATURE_HEADER = 'x-fake-signature';

    public const CHECKOUT_ROUTE = 'integrations.fake-checkout';

    private const REFERENCE_PREFIX = 'FAKEPAY-';

    private const REFERENCE_LENGTH = 12;

    public function key(): string
    {
        return self::KEY;
    }

    public function createLink(PaymentLinkRequest $request): PaymentLink
    {
        $reference = self::REFERENCE_PREFIX . mb_strtoupper(substr(hash('sha256', $request->idempotencyKey), 0, self::REFERENCE_LENGTH));

        return new PaymentLink(
            url: URL::temporarySignedRoute(self::CHECKOUT_ROUTE, $request->expiresAt, [
                'reference' => $reference,
                'amount' => $request->amount->getMinorAmount()->toInt(),
                'currency' => $request->amount->getCurrency()->getCurrencyCode(),
            ]),
            gatewayReference: $reference,
        );
    }

    public function parseWebhook(string $rawBody, array $headers): GatewayEvent
    {
        $signature = $headers[self::SIGNATURE_HEADER] ?? '';
        if (! hash_equals(self::sign($rawBody), $signature)) {
            throw PaymentRuleViolation::invalidSignature();
        }

        try {
            /** @var array{event_id?: string, reference?: string, outcome?: string} $payload */
            $payload = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw PaymentRuleViolation::malformedWebhook();
        }

        $outcome = PaymentStatus::tryFrom((string) ($payload['outcome'] ?? ''));
        if (! isset($payload['event_id'], $payload['reference']) || ! in_array($outcome, [PaymentStatus::Approved, PaymentStatus::Rejected], true)) {
            throw PaymentRuleViolation::malformedWebhook();
        }

        return new GatewayEvent($payload['event_id'], $payload['reference'], $outcome);
    }

    /** Firma con el secreto del entorno (nunca en el código). */
    public static function sign(string $rawBody): string
    {
        return hash_hmac('sha256', $rawBody, config()->string('services.payments.fake.webhook_secret'));
    }
}
