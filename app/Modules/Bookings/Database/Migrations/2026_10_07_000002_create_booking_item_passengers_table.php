<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_item_passengers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_item_id')->constrained('booking_items')->cascadeOnDelete();
            $table->foreignId('traveler_id')->constrained('travelers')->restrictOnDelete();
            // Edad y tipo a la fecha del servicio, congelados al asignar (la fecha de nacimiento sigue cifrada en el CRM).
            $table->unsignedSmallInteger('age_at_service');
            $table->string('passenger_type', 10);
            $table->timestamps();

            $table->unique(['booking_item_id', 'traveler_id'], 'booking_item_passengers_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_item_passengers');
    }
};
