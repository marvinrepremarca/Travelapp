<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Bitácora de eventos de integración y entregas por suscriptor (ADR-0007): una capacidad apagada
 * reconoce al encenderse lo que ocurrió mientras tanto. Ambas tablas son de solo inserción.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('integration_events', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('name', 100);
            $table->json('payload');
            $table->dateTime('occurred_at');
            $table->index(['name', 'id'], 'integration_events_name_id_index');
        });

        Schema::create('integration_event_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_event_id')->constrained('integration_events')->restrictOnDelete();
            $table->string('subscriber', 191);
            $table->string('outcome', 20);
            $table->dateTime('delivered_at');
            $table->unique(['subscriber', 'integration_event_id'], 'ie_deliveries_subscriber_event_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_event_deliveries');
        Schema::dropIfExists('integration_events');
    }
};
