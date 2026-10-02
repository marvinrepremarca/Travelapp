<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('number', 30)->nullable()->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            // Una cotización se convierte en un solo expediente.
            $table->ulid('quote_ulid')->nullable()->unique();
            $table->string('quote_number', 30)->nullable();
            $table->unsignedInteger('quote_version')->nullable();
            $table->string('title');
            $table->char('sale_currency', 3);
            $table->string('status', 30);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'status'], 'bookings_owner_status_index');
            $table->index(['branch_id', 'status'], 'bookings_branch_status_index');
        });

        Schema::create('booking_items', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('product_type', 20);
            $table->string('description');
            $table->ulid('catalog_product_ulid')->nullable();
            $table->ulid('catalog_departure_ulid')->nullable();
            $table->ulid('seat_hold_ulid')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->char('destination_country', 2)->nullable();
            $table->date('service_date');
            $table->unsignedSmallInteger('nights')->default(0);
            $table->json('passenger_ages');
            $table->unsignedBigInteger('net_amount_minor');
            $table->char('net_currency', 3);
            $table->unsignedBigInteger('sale_amount_minor');
            $table->bigInteger('margin_amount_minor');
            $table->json('price_breakdown');
            $table->string('status', 30);
            $table->string('supplier_confirmation', 100)->nullable();
            $table->text('status_note')->nullable();
            $table->dateTime('status_changed_at');
            $table->timestamps();

            $table->index(['status', 'service_date'], 'booking_items_status_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
        Schema::dropIfExists('bookings');
    }
};
