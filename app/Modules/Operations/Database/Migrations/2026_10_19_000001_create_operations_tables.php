<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Operación en destino: guías, vehículos y su asignación a las salidas del producto propio. */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('guides', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('name', 150);
            $table->string('phone', 30);
            // Idiomas que habla, separados por coma (texto libre para la demo).
            $table->string('languages', 150)->nullable();
            // Tarjeta profesional de guía de turismo (Colombia).
            $table->string('license_number', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('plate', 15)->unique();
            $table->string('description', 150);
            $table->unsignedSmallInteger('capacity');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('departure_assignments', function (Blueprint $table): void {
            $table->id();
            $table->ulid('departure_ulid')->unique();
            $table->foreignId('guide_id')->nullable()->constrained('guides')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            // Ventana de la salida en UTC (para detectar choques de horario entre salidas).
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['guide_id', 'starts_at'], 'departure_assignments_guide_index');
            $table->index(['vehicle_id', 'starts_at'], 'departure_assignments_vehicle_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departure_assignments');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('guides');
    }
};
