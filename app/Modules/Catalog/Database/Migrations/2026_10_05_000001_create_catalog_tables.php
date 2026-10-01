<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalog_products', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('product_type', 20);
            $table->text('description')->nullable();
            $table->char('destination_country', 2);
            $table->string('destination_city', 100);
            // Zona del destino: las salidas son horas locales del lugar del servicio.
            $table->string('timezone', 64);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->char('currency', 3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'product_type'], 'catalog_products_active_type_index');
        });

        Schema::create('catalog_seasons', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('product_id')->constrained('catalog_products')->cascadeOnDelete();
            $table->string('name', 100);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->timestamps();

            $table->index(['product_id', 'starts_on', 'ends_on'], 'catalog_seasons_product_range_index');
        });

        Schema::create('catalog_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('season_id')->constrained('catalog_seasons')->cascadeOnDelete();
            $table->string('passenger_type', 10);
            // Costo neto por pasajero en la moneda del producto; el precio de venta lo calcula Pricing.
            $table->unsignedBigInteger('net_amount_minor');
            $table->timestamps();

            $table->unique(['season_id', 'passenger_type'], 'catalog_rates_season_type_unique');
        });

        Schema::create('catalog_departures', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('product_id')->constrained('catalog_products')->cascadeOnDelete();
            $table->date('service_date');
            $table->time('starts_at');
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('reserved_seats')->default(0);
            $table->string('status', 20);
            $table->timestamps();

            $table->unique(['product_id', 'service_date', 'starts_at'], 'catalog_departures_slot_unique');
            $table->index(['service_date', 'status'], 'catalog_departures_date_status_index');
        });

        Schema::create('catalog_seat_holds', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('departure_id')->constrained('catalog_departures')->cascadeOnDelete();
            $table->unsignedInteger('seats');
            $table->string('status', 20);
            $table->string('reference', 100);
            // El mismo pedido repetido (reintento, doble clic) no descuenta cupo dos veces.
            $table->string('idempotency_key', 100)->unique();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_seat_holds');
        Schema::dropIfExists('catalog_departures');
        Schema::dropIfExists('catalog_rates');
        Schema::dropIfExists('catalog_seasons');
        Schema::dropIfExists('catalog_products');
    }
};
