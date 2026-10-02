<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Data;

/**
 * Opción aceptada de una cotización, con sus ítems tal como se aceptaron (precios congelados de la versión enviada).
 *
 * @phpstan-type AcceptedItem array{
 *     kind: string, product_type: string, description: string, catalog_product_ulid: string|null, supplier_id: int|null,
 *     destination_country: string|null, service_date: string, nights: int, passenger_ages: list<int>,
 *     net_amount_minor: int, net_currency: string, sale_amount_minor: int, margin_amount_minor: int, price_breakdown: array<string, mixed>
 * }
 */
final readonly class AcceptedOption
{
    /**
     * @param  list<AcceptedItem>  $items
     */
    public function __construct(
        public string $quoteUlid,
        public string $quoteNumber,
        public int $version,
        public int $customerId,
        public int $ownerId,
        public ?int $branchId,
        public string $saleCurrency,
        public string $title,
        public array $items,
    ) {}
}
