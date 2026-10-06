<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Adapters\FakeWhatsApp;

use App\Modules\Communications\Contracts\MessagingChannel;
use App\Modules\Communications\Data\InboundMessage;
use App\Modules\Communications\Data\OutboundMessage;
use App\Modules\Communications\Data\SendResult;
use App\Modules\Communications\Exceptions\InvalidInboundMessage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use JsonException;

/**
 * WhatsApp simulado para la demo. Los mensajes salientes "se entregan" al instante (el simulador los muestra)
 * y los entrantes llegan como un webhook firmado con HMAC, igual que lo haría un proveedor real.
 */
final class FakeWhatsAppChannel implements MessagingChannel
{
    public const KEY = 'fake_whatsapp';

    public const SIGNATURE_HEADER = 'x-fake-whatsapp-signature';

    private const HASH = 'sha256';

    private const ID_PREFIX = 'fakewa_';

    public function key(): string
    {
        return self::KEY;
    }

    public function send(OutboundMessage $message): SendResult
    {
        return new SendResult(true, self::ID_PREFIX . 'out_' . $message->localId);
    }

    public function parseInbound(string $rawBody, array $headers): InboundMessage
    {
        if (! hash_equals($this->sign($rawBody), $headers[self::SIGNATURE_HEADER] ?? '')) {
            throw InvalidInboundMessage::because('signature');
        }

        try {
            /** @var array{id?: string, from?: string, text?: string, name?: string|null, timestamp?: string} $payload */
            $payload = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw InvalidInboundMessage::because('payload');
        }

        if (! isset($payload['id'], $payload['from'], $payload['text']) || trim($payload['text']) === '') {
            throw InvalidInboundMessage::because('payload');
        }

        return new InboundMessage(
            providerMessageId: $payload['id'],
            from: $payload['from'],
            body: Str::limit(trim($payload['text']), config()->integer('travel.communications.max_message_length'), ''),
            receivedAt: isset($payload['timestamp']) ? CarbonImmutable::parse($payload['timestamp']) : CarbonImmutable::now(),
            profileName: $payload['name'] ?? null,
        );
    }

    /**
     * Cuerpo y cabeceras de un webhook como los enviaría el proveedor (lo usa el simulador).
     *
     * @return array{body: string, headers: array<string, string>}
     */
    public function webhookFor(string $from, string $text, ?string $name): array
    {
        $body = json_encode([
            'id' => self::ID_PREFIX . 'in_' . Str::ulid(),
            'from' => $from,
            'text' => $text,
            'name' => $name,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR);

        return ['body' => $body, 'headers' => [self::SIGNATURE_HEADER => $this->sign($body)]];
    }

    private function sign(string $body): string
    {
        return hash_hmac(self::HASH, $body, config()->string('services.whatsapp.fake.webhook_secret'));
    }
}
