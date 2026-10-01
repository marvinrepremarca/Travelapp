<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Database\Seeders;

use App\Modules\Pricing\Models\TaxRule;
use Illuminate\Database\Seeder;

/**
 * IVA general de Colombia confirmado por la agencia (19 %, vigente desde la Ley 1819 de 2016),
 * aplicado sobre el ingreso de la agencia como intermediaria. Idempotente; se edita desde la pantalla de reglas.
 */
final class TaxReferenceSeeder extends Seeder
{
    private const NAME = 'IVA';

    private const RATE_BASIS_POINTS = 1900;

    private const VALID_FROM = '2017-01-01';

    public function run(): void
    {
        TaxRule::query()->firstOrCreate(
            ['name' => self::NAME, 'valid_from' => self::VALID_FROM],
            ['rate_basis_points' => self::RATE_BASIS_POINTS, 'exempt_product_types' => [], 'is_active' => true],
        );
    }
}
