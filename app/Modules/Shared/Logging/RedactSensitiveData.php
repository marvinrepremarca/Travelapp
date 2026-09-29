<?php

declare(strict_types=1);

namespace App\Modules\Shared\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Reemplaza por un marcador los valores de claves sensibles (credenciales, tarjetas, documentos,
 * datos personales) en el contexto y los extras de cada registro, a cualquier profundidad.
 */
final class RedactSensitiveData implements ProcessorInterface
{
    public const MASK = '[REDACTED]';

    /** Coincidencia por subcadena, sin distinguir mayúsculas. */
    private const SENSITIVE_KEY_FRAGMENTS = [
        'password', 'secret', 'token', 'authorization', 'api_key', 'apikey', 'cookie',
        'card', 'pan', 'cvv', 'cvc', 'passport', 'document_number', 'birth', 'national_id',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            context: $this->redact($record->context),
            extra: $this->redact($record->extra),
        );
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    private function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSensitive($key)) {
                $data[$key] = self::MASK;
            } elseif (is_array($value)) {
                $data[$key] = $this->redact($value);
            }
        }

        return $data;
    }

    private function isSensitive(string $key): bool
    {
        $key = strtolower($key);

        foreach (self::SENSITIVE_KEY_FRAGMENTS as $fragment) {
            if (str_contains($key, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
