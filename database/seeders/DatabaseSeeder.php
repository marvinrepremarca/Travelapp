<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Pricing\Database\Seeders\TaxReferenceSeeder;
use App\Modules\Search\Database\Seeders\AirportsSeeder;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            TaxReferenceSeeder::class,
            AirportsSeeder::class,
        ]);
    }
}
