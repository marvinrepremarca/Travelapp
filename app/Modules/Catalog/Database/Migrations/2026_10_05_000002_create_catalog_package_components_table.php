<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalog_package_components', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('package_id')->constrained('catalog_products')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('catalog_products')->restrictOnDelete();
            // Día del itinerario relativo al inicio del paquete (0 = primer día).
            $table->unsignedSmallInteger('day_offset');
            $table->timestamps();

            $table->unique(['package_id', 'component_id', 'day_offset'], 'catalog_package_components_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_package_components');
    }
};
