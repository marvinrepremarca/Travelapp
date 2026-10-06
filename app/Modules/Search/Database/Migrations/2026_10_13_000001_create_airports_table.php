<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Aeropuertos de referencia para el autocompletar de lugares (los carga AirportsSeeder). */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('airports', function (Blueprint $table): void {
            $table->id();
            $table->char('iata_code', 3)->unique();
            $table->string('city', 100);
            $table->string('name', 150);
            $table->char('country_code', 2);
            // Menor = más relevante en las sugerencias.
            $table->unsignedSmallInteger('priority');
            // Texto normalizado (sin tildes, minúsculas) con código, ciudad, aeropuerto y país.
            $table->string('search_text', 400);

            $table->index(['priority'], 'airports_priority_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('airports');
    }
};
