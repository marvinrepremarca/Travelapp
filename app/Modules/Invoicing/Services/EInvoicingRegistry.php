<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Services;

use App\Modules\Invoicing\Contracts\EInvoicingProvider;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/** Proveedor de facturación electrónica activo según configuración (cambiar de proveedor = cambiar .env). */
final readonly class EInvoicingRegistry
{
    public function __construct(private Container $container) {}

    public function active(): EInvoicingProvider
    {
        $key = config()->string('travel.invoicing.e_invoicing_provider');
        foreach ($this->container->tagged(EInvoicingProvider::TAG) as $provider) {
            if ($provider instanceof EInvoicingProvider && $provider->key() === $key) {
                return $provider;
            }
        }

        throw new InvalidArgumentException("Proveedor de facturación electrónica no registrado: {$key}");
    }
}
